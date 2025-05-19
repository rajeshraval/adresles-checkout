jQuery(function ($) {
	let pollInterval = null;
	const pollDelay = 15000; // 15 seconds
	const registerUrl = adreslesData.register_url;
	let pollAttempt = 0;

	// Fetch user by phone using token
	async function fetchUserByPhone(phone) {
		if (!phone) return null;

        // Show loading message
	    if (!$('.adresles-fetching-message').length && pollAttempt === 0) {
			pollAttempt++;
            const $loader = $('<div class="adresles-fetching-message" style="margin-top: 8px; padding: 10px; background: #fff3cd; color: #856404;">🕐 Obteniendo tus datos...</div>');
            $('#adresles_mobile').after($loader);
        }

		try {
			const cartDetail = await fetch('/wp-admin/admin-ajax.php?action=get_cart_summary')
			.then(response => response.json())
			.then(data => {
				if (data.success === false) {
					console.error('Error:', data.data);
				} else {
					return data;
				}
			})
			.catch(error => console.error('Fetch error:', error));

			if(!cartDetail) return;

			const response = await fetch(adreslesData.api_path + 'adresles/v1/get-user-by-phone/', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				credentials: 'same-origin',
				body: JSON.stringify({phone: phone, ...cartDetail})
			});

			if (response.status === 404) return null;
			if (!response.ok) throw new Error('Invalid response');
			return await response.json();
		} catch (error) {
			console.error('Error fetching user:', error);
			return null;
		} finally {
            // Remove loading message
            $('.adresles-fetching-message').fadeOut(300, function () {
                $(this).remove();
            });
        }
	}

    function getCountryCodeByLabel(label) {
        let code = '';
        $('#billing_country option').each(function () {
			if (label && $(this).text().trim().toLowerCase() === label.trim().toLowerCase()) {
                code = $(this).val();
                return false; // stop loop
            }
        });
        return code;
    }    

	// Fill billing/shipping fields
	function fillAddressFields(data, phone) {
		if (!data || !data.userData) return;

		const d = data.userData;
		const job = data.job;

		// Split full name
        const nameParts = (d.name || '').trim().split(' ');
        const firstName = nameParts.slice(0, -1).join(' ') || d.name;
        const lastName = nameParts.slice(-1).join(' ') || '';

        // Dynamically get country/state codes from Woo dropdowns
        const countryCode = getCountryCodeByLabel(d.country);

        $('#billing_first_name, #shipping_first_name').val(firstName);
        $('#billing_last_name, #shipping_last_name').val(lastName);
        $('#billing_address_1, #shipping_address_1').val(d.default_address || '');
        $('#billing_address_2, #shipping_address_2').val('');
        $('#billing_city, #shipping_city').val(d.city || 'Bucaramanga');
        $('#billing_postcode, #shipping_postcode').val(d.zip_code || '68001');
        $('#billing_state, #shipping_state').val('CO-TOL');
        $('#billing_country, #shipping_country').val('CO');

        $('#billing_email').val(d.email || '');
        $('#billing_phone').val(d.phone || phone);

		$('.adresles-notice').hide();
		
		let message = '✅ Dirección obtenida correctamente.';
		if(job.state == 'IN_PROGRESS') {
			message = '✅ Esperando confirmación de datos.';
		}
		$('.temp-msg-div')
				.text(message)
				.css({ background: '#d4edda', color: '#155724', padding: '10px', display: 'block' })
				.show();

	}

	// Show register fallback message
	function showError(message) {
		$('.adresles-notice').hide();
		$('.temp-msg-div')
			.html(`${message} <a href="${registerUrl}" target="_blank" rel="noopener noreferrer" class="adresles-register-link">Click here to register</a>`)
			.css({ color: 'red', display: 'block' })
			.show();
	}

	// Poll address every X seconds
	function startPolling(phone) {
		if (pollInterval) clearInterval(pollInterval);
		pollInterval = setInterval(async () => {
			const data = await fetchUserByPhone(phone);
			if (data) {
				fillAddressFields(data, phone);
				clearInterval(pollInterval);
				pollInterval = null;
			}
		}, pollDelay);
	}

	// Phone blur = trigger API
	async function onPhoneBlur() {
		const phone = $('#adresles_mobile').val().trim();
		pollAttempt = 0;
		if (pollInterval) clearInterval(pollInterval);
		if (!phone) return;
		if(phone.length < 10) return;
		const data = await fetchUserByPhone(phone);

		if (data) {
			fillAddressFields(data, phone);
			if (pollInterval) clearInterval(pollInterval);
		} else {
			showError('No address found for this phone number.');
		}
	}

	// UI toggles
	function toggleAdreslesLogic() {
		const adreslesChecked = $('#adresles_checkout_selected_field input').is(':checked');
		const $giftCheckbox = $('#adresles_gift_selected_field input');
		const $adreslesPhone = $('#adresles_mobile_field input');

		if (adreslesChecked) {
			$('.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields, .woocommerce-additional-fields').hide();
			$giftCheckbox.prop('disabled', false);
			$adreslesPhone.prop('disabled', false);
		} else {
			$giftCheckbox.prop('checked', false).prop('disabled', true);
			toggleGiftFields();
			$('.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields, .woocommerce-additional-fields').show();
			$adreslesPhone.prop('disabled', true);			
		}
	}

	let originalShippingParent = null;

	jQuery(function ($) {
		// On DOM ready, store the original parent
		originalShippingParent = $('.woocommerce-shipping-fields').parent();
	});

	function toggleGiftFields() {
		const giftChecked = $('#adresles_gift_selected_field input').is(':checked');
		const $giftArea = $('#adresles_gift_shippping_section');
		const $shippingFields = $('.woocommerce-shipping-fields');

		if (giftChecked) {
			// Move shipping fields into gift area
			if (!$giftArea.find('.woocommerce-shipping-fields').length) {
				$shippingFields.appendTo($giftArea);
			}
			$('.woocommerce-shipping-fields').show();
			$('#ship-to-different-address').hide();
			$('input#ship-to-different-address-checkbox').prop('checked', true).trigger('change');
			$giftArea.slideDown();

		} else {
			$giftArea.slideUp();
			$('.woocommerce-shipping-fields').hide();			
			// Move it back to original container
			if (originalShippingParent && !originalShippingParent.find('.woocommerce-shipping-fields').length) {
				$shippingFields.appendTo(originalShippingParent);
				$('#ship-to-different-address').show();
				$('input#ship-to-different-address-checkbox').prop('checked', false).trigger('change');
			}
		}
	}
	
	// Event bindings
	$('#adresles_checkout_selected_field input').change(function () {
		toggleAdreslesLogic();
	});

	$('#adresles_gift_selected_field input').change(toggleGiftFields);

	let debounceTimer;
	$('#adresles_mobile').on('keypress', function (e) {
		if (e.which < 48 || e.which > 57) {
			e.preventDefault();
		}
		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(() => {
			onPhoneBlur();
		}, 1000); // 1 seconds
	});

	$('#adresles_mobile').on('paste', function(e) {
		e.preventDefault();
		var pastedData = (e.originalEvent || e).clipboardData.getData('text');

		pastedData = pastedData.replace(/[^\d+]/g, '');

		if (pastedData.indexOf('+') > 0) {
			pastedData = pastedData.replace(/\+/g, '');
		} else if ((pastedData.match(/\+/g) || []).length > 1) {
			pastedData = pastedData.replace(/\+/g, ''); 
		}

		$(this).val(pastedData);
		onPhoneBlur();
	});

	$(document).on('click', '.adresles-register-link', function (e) {
		e.preventDefault();
		const phone = $('#adresles_mobile').val();
		if(phone) {
			startPolling(phone);
		}
		window.open(registerUrl, '_blank', 'noopener');
	});

	// Init on load
	toggleAdreslesLogic();
});
