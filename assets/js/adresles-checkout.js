jQuery(function ($) {
	let pollInterval = null;
	const pollDelay = 5000; // 15 seconds
	const registerUrl = adreslesData.register_url;
	let pollAttempt = 0;
	const plugin_dir_url = adreslesData.plugin_dir_url;
	const field_mappings_status = adreslesData.field_mappings_status || false;
	const site_url = adreslesData.site_url;
	const field_mapping_arr = adreslesData.field_mapping_arr;

	console.log(field_mapping_arr);

	// Fetch user by phone using token
	async function fetchUserByPhone(phone) {
		if (!phone) return null;

        // Show loading message
	    if (!$('.adresles-fetching-message').length && pollAttempt === 0) {
			const $loader = $(`<img class="adresles-fetching-message" alt="" style="float:right;margin-top:5px;height:32px;width:32px;" src="${plugin_dir_url}assets/images/loader.gif" />`);
            $('#adresles_mobile').after($loader);
        }

		try {

			console.log('pollAttempt', pollAttempt);
			let cartDetail = {};

			if(pollAttempt === 0) {
				cartDetail = await fetch(site_url + '/wp-admin/admin-ajax.php?action=get_cart_summary')
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
			}
			pollAttempt++;

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

		Object.entries(field_mapping_arr).forEach(([fieldId, userKey]) => {
			// Skip country/state, handle them separately
			if (
				['billing_country', 'shipping_country', 'billing_state', 'shipping_state'].includes(fieldId)
			) {
				return;
			}

			const value = d[userKey];
			if (value !== undefined && value !== null) {
				$(`#${fieldId}`).val(value);
			}
		});

		// Country and state defaults (hardcoded)
		$('#billing_country, #shipping_country').val('CO');
		$('#billing_state, #shipping_state').val('CO-TOL');
	}

	// Show register fallback message
	function showError(message) {
		$('.adresles-notice').hide();
		$('.temp-msg-div')
			.html(`${message} <a href="${registerUrl}" target="_blank" rel="noopener noreferrer" class="adresles-register-link" style="font-size:16px;display:block;color:#1D4D6E;font-weight:600">Click here to register</a>`)
			.css({ color: 'red', display: 'block', background:"#FFFFFF", fontSize: '20px', textTransform: 'capitalize'})
			.show();
	}

	// Poll address every X seconds
	function startPolling(phone) {
		if (pollInterval) clearInterval(pollInterval);
		pollInterval = setInterval(async () => {
			const data = await fetchUserByPhone(phone);
			if(data.job.state == "IN_PROGRESS"){
				$('.temp-msg-div')
				.text('⏱️ Esperando confirmación de datos.')
				.css({ background: '#E0E621', color: '#000000', padding: '10px', display: 'block' })
				.show();			
				startPolling(phone);
			}else{
				$('.temp-msg-div')
				.text('✅ Dirección obtenida correctamente.')
				.css({ background: '#d4edda', color: '#155724', padding: '10px', display: 'block' })
				.show();
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
			console.log('data', data.job.state)
			if(data.job.state == "IN_PROGRESS"){
				$('.temp-msg-div')
				.text('⏱️ Esperando confirmación de datos.')
				.css({ background: '#E0E621', color: '#000000', padding: '10px', display: 'block' })
				.show();				
				startPolling(phone);
			} else {
				$('.temp-msg-div')
				.text('✅ Dirección obtenida correctamente.')
				.css({ background: '#d4edda', color: '#155724', padding: '10px', display: 'block' })
				.show();
				fillAddressFields(data, phone);
				if (pollInterval) clearInterval(pollInterval);
			}			
		} else {
			showError('No address found for this phone number.');
		}
	}

	// UI toggles
	function toggleAdreslesLogic() {
		const adreslesChecked = $('#adresles_checkout_selected_field input').is(':checked');
		const $giftCheckbox = $('#adresles_gift_selected_field input');
		const $adreslesPhone = $('#adresles_mobile_field input');

		$('.temp-msg-div').hide();
		if (adreslesChecked) {
			$('#adresles_mobile_field_wapper,#adresles_gift_section').show();
			$('.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields, .woocommerce-additional-fields').hide();
			$giftCheckbox.prop('disabled', false);
			$adreslesPhone.prop('disabled', false);
		} else {
			$giftCheckbox.prop('checked', false).prop('disabled', true);
			// toggleGiftFields();
			$('.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields, .woocommerce-additional-fields').show();
			$adreslesPhone.prop('disabled', true);			
			$('#adresles_mobile_field_wapper,#adresles_gift_section').hide();
		}
	}

	let originalShippingParent = null;

	jQuery(function ($) {
		// On DOM ready, store the original parent
		originalShippingParent = $('.woocommerce-shipping-fields').parent();
	});

	// function toggleGiftFields() {
	// 	const giftChecked = $('#adresles_gift_selected_field input').is(':checked');
	// 	const $giftArea = $('#adresles_gift_shippping_section');
	// 	const $shippingFields = $('.woocommerce-shipping-fields');

	// 	if (giftChecked) {
	// 		// Move shipping fields into gift area
	// 		if (!$giftArea.find('.woocommerce-shipping-fields').length) {
	// 			$shippingFields.appendTo($giftArea);
	// 		}
	// 		$('.woocommerce-shipping-fields').show();
	// 		$('#ship-to-different-address').hide();
	// 		$('input#ship-to-different-address-checkbox').prop('checked', true).trigger('change');
	// 		$giftArea.slideDown();

	// 	} else {
	// 		$giftArea.slideUp();
	// 		$('.woocommerce-shipping-fields').hide();			
	// 		// Move it back to original container
	// 		if (originalShippingParent && !originalShippingParent.find('.woocommerce-shipping-fields').length) {
	// 			$shippingFields.appendTo(originalShippingParent);
	// 			$('#ship-to-different-address').show();
	// 			$('input#ship-to-different-address-checkbox').prop('checked', false).trigger('change');
	// 		}
	// 	}
	// }
	
	// Event bindings
	$('#adresles_checkout_selected_field input').change(function () {
		toggleAdreslesLogic();
	});

	// $('#adresles_gift_selected_field input').change(toggleGiftFields);

	let debounceTimer;
	$('#adresles_mobile').on('keypress', function (e) {
		if (e.which < 48 || e.which > 57) {
			e.preventDefault();
		}
		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(() => {
			if(!field_mappings_status){
				$('.temp-msg-div')
				.html('⚠️ Esta función no está funcionando en el sitio web porque hay configuraciones pendientes por parte del administrador.')
				.css({ color: 'red', display: 'block', background:"#FFFFFF", fontSize: '20px', textTransform: 'capitalize'})
				.show();
				return;
			}
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
