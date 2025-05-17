jQuery(function ($) {
	let adreslesToken = null;
	let pollInterval = null;
	const pollDelay = 5000; // 5 seconds
	const registerUrl = adreslesData.register_url;

	// Utility: Fetch JWT token from REST API
	async function fetchToken() {
		if (adreslesToken) return adreslesToken;

		try {
			const response = await fetch(adreslesData.api_path + 'adresles/v1/generate-token/', {
				method: 'GET',
				credentials: 'same-origin'
			});
			const data = await response.json();
			if (!data.token) throw new Error('No token returned');
			adreslesToken = data.token;
			return adreslesToken;
		} catch (error) {
			console.error('Error fetching token:', error);
			return null;
		}
	}

	// Fetch user by phone using token
	async function fetchUserByPhone(phone) {
		if (!phone) return null;

		const token = await fetchToken();
		if (!token) {
			showError('No token available. Please try again later.');
			return null;
		}

        // Show loading message
	    if (!$('.adresles-fetching-message').length) {
            const $loader = $('<div class="adresles-fetching-message" style="margin-top: 8px; padding: 10px; background: #fff3cd; color: #856404;">🕐 Obteniendo tus datos...</div>');
            $('#adresles_mobile').after($loader);
        }        

		try {
			const response = await fetch(adreslesData.api_path + 'adresles/v1/get-user-by-phone/', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					Authorization: 'Bearer ' + token
				},
				credentials: 'same-origin',
				body: JSON.stringify({ phone: phone })
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
            if ($(this).text().trim().toLowerCase() === label.trim().toLowerCase()) {
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

		// Split full name
        const nameParts = d.name.trim().split(' ');
        const firstName = nameParts.slice(0, -1).join(' ') || d.name;
        const lastName = nameParts.slice(-1).join(' ') || '';

        // Dynamically get country/state codes from Woo dropdowns
        const countryCode = getCountryCodeByLabel(d.country);

        $('#billing_first_name, #shipping_first_name').val(firstName);
        $('#billing_last_name, #shipping_last_name').val(lastName);
        $('#billing_address_1, #shipping_address_1').val(d.address || '');
        $('#billing_city, #shipping_city').val(d.city || '');
        $('#billing_postcode, #shipping_postcode').val(d.zip_code || '');
        $('#billing_state, #shipping_state').val('CO-TOL');
        $('#billing_country, #shipping_country').val(countryCode);
        $('#billing_email').val(d.email || '');
        $('#billing_phone').val(d.phone || phone);

		$('.adresles-notice').hide();
		$('.temp-msg-div')
			.text('✅ Dirección obtenida correctamente.')
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
		if (!phone) return;

		const data = await fetchUserByPhone(phone);

		if (data) {
			fillAddressFields(data, phone);
			if (pollInterval) clearInterval(pollInterval);
		} else {
			showError('No address found for this phone number.');
			startPolling(phone);
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
			$('.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields, .woocommerce-additional-fields').show();
			$giftCheckbox.prop('checked', false).prop('disabled', true);
			$adreslesPhone.prop('disabled', true);
			$('#gift_name_field, #gift_lastname_field, #gift_phone_field, #gift_note_field').hide();
		}
	}

	function toggleGiftFields() {
        const giftChecked = $('#adresles_gift_selected_field input').is(':checked');
        const fields = '#gift_name_field, #gift_lastname_field, #gift_phone_field, #gift_note_field';
        $(fields).slideToggle(giftChecked);
    }

	// Event bindings
	$('#adresles_checkout_selected_field input').change(function () {
		toggleAdreslesLogic();
		// toggleGiftFields();
	});

	$('#adresles_gift_selected_field input').change(toggleGiftFields);

	$('#adresles_mobile').on('blur', onPhoneBlur);

	$(document).on('click', '.adresles-register-link', function (e) {
		e.preventDefault();
		window.open(registerUrl, '_blank', 'noopener');
	});

	// Init on load
	toggleAdreslesLogic();
});
