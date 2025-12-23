/**
 * Admin Order Form V2 - Matrix-Based Pricing with Customer Selection
 * 
 * Handles admin-specific order creation with customer search/creation
 * and V2 pricing engine integration
 * 
 * @package Tabesh
 */

(function($) {
	'use strict';

	// Current step tracking.
	let currentStep = 1;
	const totalSteps = 5;

	// Form state.
	const formState = {
		customer_id: null,
		customer_type: 'existing',
		new_customer_data: null,
		book_title: '',
		book_size: '',
		paper_type: '',
		paper_weight: '',
		print_type: '',
		page_count: 100,
		quantity: 10,
		binding_type: '',
		cover_weight: '',
		extras: [],
		notes: '',
		send_sms: false,
		calculated_price: null
	};

	// Search timeout.
	let searchTimeout = null;

	/**
	 * Initialize wizard on document ready
	 */
	$(document).ready(function() {
		initAdminWizard();
	});

	/**
	 * Initialize admin wizard
	 */
	function initAdminWizard() {
		if ($('#tabesh-admin-wizard-form').length === 0) {
			return;
		}

		console.log('Initializing Tabesh Admin Order Form V2...');

		// Attach event listeners.
		attachEventListeners();

		// Initialize first step.
		showStep(1);
	}

	/**
	 * Attach all event listeners
	 */
	function attachEventListeners() {
		// Navigation buttons.
		$('#adminNextBtn').on('click', handleNext);
		$('#adminPrevBtn').on('click', handlePrevious);
		$('#adminSubmitBtn').on('click', handleSubmit);

		// Customer type toggle.
		$('input[name="customer_type"]').on('change', function() {
			const type = $(this).val();
			formState.customer_type = type;
			
			if (type === 'existing') {
				$('#existing-customer-section').show();
				$('#new-customer-section').hide();
				formState.customer_id = null;
				formState.new_customer_data = null;
			} else {
				$('#existing-customer-section').hide();
				$('#new-customer-section').show();
				formState.customer_id = null;
				$('#selected_customer_id').val('');
				$('#selected_customer_display').empty();
			}
		});

		// Customer search.
		$('#customer_search').on('input', function() {
			const search = $(this).val().trim();
			
			clearTimeout(searchTimeout);
			
			if (search.length < 2) {
				$('#customer_search_results').empty();
				return;
			}

			searchTimeout = setTimeout(function() {
				searchCustomers(search);
			}, 300);
		});

		// Close search results when clicking outside.
		$(document).on('click', function(e) {
			if (!$(e.target).closest('#customer_search, #customer_search_results').length) {
				$('#customer_search_results').empty();
			}
		});

		// Create customer button.
		$('#create_customer_btn').on('click', createCustomer);

		// Form field changes.
		$('#book_title_admin').on('input', function() {
			formState.book_title = $(this).val();
		});

		$('input[name="book_size"]').on('change', function() {
			const bookSize = $(this).val();
			formState.book_size = bookSize;
			loadAllowedOptions({ book_size: bookSize });
		});

		$('#paper_type_admin').on('change', function() {
			const paperType = $(this).val();
			formState.paper_type = paperType;
			loadPaperWeights(paperType);
		});

		$('#paper_weight_admin').on('change', function() {
			formState.paper_weight = $(this).val();
			loadPrintTypes();
		});

		$(document).on('change', 'input[name="print_type"]', function() {
			formState.print_type = $(this).val();
		});

		$('#page_count_admin').on('input', function() {
			formState.page_count = parseInt($(this).val(), 10);
		});

		$('#quantity_admin').on('input', function() {
			formState.quantity = parseInt($(this).val(), 10);
		});

		$('#binding_type_admin').on('change', function() {
			const bindingType = $(this).val();
			formState.binding_type = bindingType;
			loadCoverWeights();
			loadExtras();
		});

		$('#cover_weight_admin').on('change', function() {
			formState.cover_weight = $(this).val();
		});

		$(document).on('change', '#extras_container_admin input[type="checkbox"]', function() {
			updateExtrasState();
		});

		$('#notes_admin').on('input', function() {
			formState.notes = $(this).val();
		});

		$('#send_sms_admin').on('change', function() {
			formState.send_sms = $(this).is(':checked');
		});

		// Calculate price button.
		$('#calculate_price_admin_btn').on('click', calculatePrice);

		// Create another order button.
		$('#create_another_order_btn').on('click', resetForm);
	}

	/**
	 * Search customers
	 */
	function searchCustomers(search) {
		const $results = $('#customer_search_results');
		$results.html('<div class="search-loading">در حال جستجو...</div>');

		$.ajax({
			url: tabeshAdminOrderV2.restUrl + '/admin/search-users-live',
			method: 'POST',
			headers: {
				'X-WP-Nonce': tabeshAdminOrderV2.nonce
			},
			data: JSON.stringify({ search: search }),
			contentType: 'application/json',
			success: function(response) {
				if (response.users && response.users.length > 0) {
					let html = '<div class="search-results-list">';
					response.users.forEach(function(user) {
						html += '<div class="search-result-item" data-user-id="' + user.id + '" data-user-name="' + user.name + '" data-user-mobile="' + user.mobile + '" data-user-email="' + user.email + '">';
						html += '<div class="user-info">';
						html += '<strong>' + user.name + '</strong>';
						html += '<span>' + user.mobile + '</span>';
						if (user.email) {
							html += '<span>' + user.email + '</span>';
						}
						html += '</div>';
						html += '</div>';
					});
					html += '</div>';
					$results.html(html);

					// Attach click handler.
					$('.search-result-item').on('click', function() {
						selectCustomer($(this));
					});
				} else {
					$results.html('<div class="search-no-results">کاربری یافت نشد</div>');
				}
			},
			error: function() {
				$results.html('<div class="search-error">خطا در جستجو</div>');
			}
		});
	}

	/**
	 * Select customer
	 */
	function selectCustomer($item) {
		const userId = $item.data('user-id');
		const userName = $item.data('user-name');
		const userMobile = $item.data('user-mobile');
		const userEmail = $item.data('user-email');

		formState.customer_id = userId;
		$('#selected_customer_id').val(userId);

		// Display selected customer.
		let html = '<div class="selected-customer-card">';
		html += '<div class="customer-info">';
		html += '<strong>' + userName + '</strong>';
		html += '<span>' + userMobile + '</span>';
		if (userEmail) {
			html += '<span>' + userEmail + '</span>';
		}
		html += '</div>';
		html += '<button type="button" class="remove-customer">×</button>';
		html += '</div>';
		
		$('#selected_customer_display').html(html);
		$('#customer_search').val('');
		$('#customer_search_results').empty();

		// Attach remove handler.
		$('.remove-customer').on('click', function() {
			formState.customer_id = null;
			$('#selected_customer_id').val('');
			$('#selected_customer_display').empty();
		});
	}

	/**
	 * Create new customer
	 */
	function createCustomer() {
		const name = $('#new_customer_name').val().trim();
		const mobile = $('#new_customer_mobile').val().trim();
		const email = $('#new_customer_email').val().trim();

		// Validate.
		if (!name || !mobile) {
			showToast('لطفاً نام و موبایل را وارد کنید', 'error');
			return;
		}

		// Validate mobile format.
		if (!/^09[0-9]{9}$/.test(mobile)) {
			showToast(tabeshAdminOrderV2.strings.invalidMobile, 'error');
			return;
		}

		// Show loading.
		$('#create_customer_btn').prop('disabled', true).text('در حال ایجاد...');

		$.ajax({
			url: tabeshAdminOrderV2.restUrl + '/admin/create-user',
			method: 'POST',
			headers: {
				'X-WP-Nonce': tabeshAdminOrderV2.nonce
			},
			data: JSON.stringify({
				name: name,
				mobile: mobile,
				email: email
			}),
			contentType: 'application/json',
			success: function(response) {
				if (response.user_id) {
					formState.customer_id = response.user_id;
					formState.new_customer_data = {
						name: name,
						mobile: mobile,
						email: email
					};
					
					showToast(tabeshAdminOrderV2.strings.userCreated, 'success');
					
					// Display created customer.
					let html = '<div class="selected-customer-card">';
					html += '<div class="customer-info">';
					html += '<strong>' + name + '</strong>';
					html += '<span>' + mobile + '</span>';
					if (email) {
						html += '<span>' + email + '</span>';
					}
					html += '</div>';
					html += '</div>';
					
					// Switch to existing customer view with new user shown.
					$('input[name="customer_type"][value="existing"]').prop('checked', true).trigger('change');
					$('#selected_customer_id').val(response.user_id);
					$('#selected_customer_display').html(html);
					
					// Clear form.
					$('#new_customer_name').val('');
					$('#new_customer_mobile').val('');
					$('#new_customer_email').val('');
				}
			},
			error: function(xhr) {
				let message = tabeshAdminOrderV2.strings.error;
				if (xhr.responseJSON && xhr.responseJSON.message) {
					message = xhr.responseJSON.message;
				}
				showToast(message, 'error');
			},
			complete: function() {
				$('#create_customer_btn').prop('disabled', false).text('ایجاد مشتری');
			}
		});
	}

	/**
	 * Show specific step
	 */
	function showStep(step) {
		// Hide all steps.
		$('.wizard-step').removeClass('active');
		
		// Show current step.
		$(`.wizard-step[data-step="${step}"]`).addClass('active');
		
		// Update progress.
		updateProgress(step);
		
		// Update navigation buttons.
		updateNavigation(step);
		
		// Update current step.
		currentStep = step;

		// If step 5, update review.
		if (step === 5) {
			updateOrderReview();
		}
	}

	/**
	 * Update progress bar and steps
	 */
	function updateProgress(step) {
		const progress = (step / totalSteps) * 100;
		$('#adminProgressBar').css('width', progress + '%');

		// Update step indicators.
		$('.progress-step').each(function() {
			const stepNum = parseInt($(this).data('step'), 10);
			$(this).removeClass('active completed');
			
			if (stepNum < step) {
				$(this).addClass('completed');
			} else if (stepNum === step) {
				$(this).addClass('active');
			}
		});
	}

	/**
	 * Update navigation buttons
	 */
	function updateNavigation(step) {
		// Previous button.
		if (step === 1) {
			$('#adminPrevBtn').hide();
		} else {
			$('#adminPrevBtn').show();
		}

		// Next button.
		if (step === totalSteps) {
			$('#adminNextBtn').hide();
			$('#adminSubmitBtn').show();
		} else {
			$('#adminNextBtn').show();
			$('#adminSubmitBtn').hide();
		}
	}

	/**
	 * Handle next button click
	 */
	function handleNext() {
		if (!validateStep(currentStep)) {
			return;
		}

		if (currentStep < totalSteps) {
			showStep(currentStep + 1);
		}
	}

	/**
	 * Handle previous button click
	 */
	function handlePrevious() {
		if (currentStep > 1) {
			showStep(currentStep - 1);
		}
	}

	/**
	 * Validate current step
	 */
	function validateStep(step) {
		let isValid = true;
		let message = '';

		switch (step) {
			case 1:
				if (formState.customer_type === 'existing') {
					if (!formState.customer_id) {
						message = tabeshAdminOrderV2.strings.selectCustomer;
						isValid = false;
					}
				} else {
					// For new customer, check if customer was created.
					if (!formState.customer_id) {
						message = 'لطفاً ابتدا مشتری جدید را ایجاد کنید';
						isValid = false;
					}
				}
				break;

			case 2:
				if (!formState.book_title) {
					message = tabeshAdminOrderV2.strings.bookTitle;
					isValid = false;
				} else if (!formState.book_size) {
					message = tabeshAdminOrderV2.strings.selectBookSize;
					isValid = false;
				}
				break;

			case 3:
				if (!formState.paper_type) {
					message = tabeshAdminOrderV2.strings.selectPaperType;
					isValid = false;
				} else if (!formState.paper_weight) {
					message = tabeshAdminOrderV2.strings.selectPaperWeight;
					isValid = false;
				} else if (!formState.print_type) {
					message = tabeshAdminOrderV2.strings.selectPrintType;
					isValid = false;
				} else if (!formState.page_count || formState.page_count <= 0) {
					message = tabeshAdminOrderV2.strings.enterPageCount;
					isValid = false;
				} else if (!formState.quantity || formState.quantity <= 0) {
					message = tabeshAdminOrderV2.strings.enterQuantity;
					isValid = false;
				}
				break;

			case 4:
				if (!formState.binding_type) {
					message = tabeshAdminOrderV2.strings.selectBindingType;
					isValid = false;
				} else if (!formState.cover_weight) {
					message = tabeshAdminOrderV2.strings.selectCoverWeight;
					isValid = false;
				}
				break;

			case 5:
				// Final validation before submit.
				if (!formState.calculated_price) {
					message = 'لطفاً ابتدا قیمت را محاسبه کنید';
					isValid = false;
				}
				break;
		}

		if (!isValid) {
			showToast(message, 'error');
		}

		return isValid;
	}

	/**
	 * Load allowed options based on book size
	 */
	function loadAllowedOptions(currentSelection) {
		$.ajax({
			url: tabeshAdminOrderV2.restUrl + '/get-allowed-options',
			method: 'POST',
			headers: {
				'X-WP-Nonce': tabeshAdminOrderV2.nonce
			},
			data: JSON.stringify({
				book_size: currentSelection.book_size,
				current_selection: currentSelection
			}),
			contentType: 'application/json',
			success: function(response) {
				if (response.error) {
					showToast(response.message, 'error');
					return;
				}

				// Populate paper types.
				populatePaperTypes(response.allowed_papers);
				
				// Populate binding types.
				populateBindingTypes(response.allowed_bindings);
			},
			error: function() {
				showToast('خطا در بارگذاری گزینه‌ها', 'error');
			}
		});
	}

	/**
	 * Populate paper types dropdown
	 */
	function populatePaperTypes(papers) {
		const $select = $('#paper_type_admin');
		$select.empty().append('<option value="">انتخاب کنید...</option>');

		if (papers && papers.length > 0) {
			papers.forEach(function(paper) {
				$select.append('<option value="' + paper + '">' + paper + '</option>');
			});
			$select.prop('disabled', false);
		}
	}

	/**
	 * Load paper weights for selected paper type
	 */
	function loadPaperWeights(paperType) {
		if (!paperType || !formState.book_size) {
			return;
		}

		$.ajax({
			url: tabeshAdminOrderV2.restUrl + '/get-allowed-options',
			method: 'POST',
			headers: {
				'X-WP-Nonce': tabeshAdminOrderV2.nonce
			},
			data: JSON.stringify({
				book_size: formState.book_size,
				current_selection: {
					paper_type: paperType
				}
			}),
			contentType: 'application/json',
			success: function(response) {
				if (response.error) {
					showToast(response.message, 'error');
					return;
				}

				const $select = $('#paper_weight_admin');
				$select.empty().append('<option value="">انتخاب کنید...</option>');

				if (response.allowed_paper_weights && response.allowed_paper_weights.length > 0) {
					response.allowed_paper_weights.forEach(function(weight) {
						$select.append('<option value="' + weight + '">' + weight + '</option>');
					});
					$select.prop('disabled', false);
				}
			}
		});
	}

	/**
	 * Load print types based on paper type and weight
	 */
	function loadPrintTypes() {
		if (!formState.paper_type || !formState.paper_weight || !formState.book_size) {
			return;
		}

		$.ajax({
			url: tabeshAdminOrderV2.restUrl + '/get-allowed-options',
			method: 'POST',
			headers: {
				'X-WP-Nonce': tabeshAdminOrderV2.nonce
			},
			data: JSON.stringify({
				book_size: formState.book_size,
				current_selection: {
					paper_type: formState.paper_type,
					paper_weight: formState.paper_weight
				}
			}),
			contentType: 'application/json',
			success: function(response) {
				if (response.error) {
					showToast(response.message, 'error');
					return;
				}

				const $container = $('#print_type_container_admin');
				$container.empty();

				if (response.allowed_print_types && response.allowed_print_types.length > 0) {
					response.allowed_print_types.forEach(function(printType) {
						const label = printType === 'bw' ? 'سیاه و سفید' : 'رنگی';
						$container.append(
							'<label class="print-type-option">' +
							'<input type="radio" name="print_type" value="' + printType + '" required>' +
							'<span>' + label + '</span>' +
							'</label>'
						);
					});
				}
			}
		});
	}

	/**
	 * Populate binding types dropdown
	 */
	function populateBindingTypes(bindings) {
		const $select = $('#binding_type_admin');
		$select.empty().append('<option value="">انتخاب کنید...</option>');

		if (bindings && bindings.length > 0) {
			bindings.forEach(function(binding) {
				$select.append('<option value="' + binding + '">' + binding + '</option>');
			});
			$select.prop('disabled', false);
		}
	}

	/**
	 * Load cover weights for selected binding type
	 */
	function loadCoverWeights() {
		if (!formState.binding_type || !formState.book_size) {
			return;
		}

		$.ajax({
			url: tabeshAdminOrderV2.restUrl + '/get-allowed-options',
			method: 'POST',
			headers: {
				'X-WP-Nonce': tabeshAdminOrderV2.nonce
			},
			data: JSON.stringify({
				book_size: formState.book_size,
				current_selection: {
					binding_type: formState.binding_type
				}
			}),
			contentType: 'application/json',
			success: function(response) {
				if (response.error) {
					showToast(response.message, 'error');
					return;
				}

				const $select = $('#cover_weight_admin');
				$select.empty().append('<option value="">انتخاب کنید...</option>');

				if (response.allowed_cover_weights && response.allowed_cover_weights.length > 0) {
					response.allowed_cover_weights.forEach(function(weight) {
						$select.append('<option value="' + weight + '">' + weight + '</option>');
					});
					$select.prop('disabled', false);
				}
			}
		});
	}

	/**
	 * Load extras (additional services)
	 */
	function loadExtras() {
		if (!formState.book_size) {
			return;
		}

		$.ajax({
			url: tabeshAdminOrderV2.restUrl + '/get-allowed-options',
			method: 'POST',
			headers: {
				'X-WP-Nonce': tabeshAdminOrderV2.nonce
			},
			data: JSON.stringify({
				book_size: formState.book_size,
				current_selection: {}
			}),
			contentType: 'application/json',
			success: function(response) {
				if (response.error) {
					return;
				}

				const $container = $('#extras_container_admin');
				$container.empty();

				if (response.allowed_extras && response.allowed_extras.length > 0) {
					response.allowed_extras.forEach(function(extra) {
						$container.append(
							'<label class="extra-option">' +
							'<input type="checkbox" name="extras[]" value="' + extra + '">' +
							'<span>' + extra + '</span>' +
							'</label>'
						);
					});
				} else {
					$container.html('<p class="form-hint">خدمات اضافی موجود نیست</p>');
				}
			}
		});
	}

	/**
	 * Update extras state
	 */
	function updateExtrasState() {
		formState.extras = [];
		$('#extras_container_admin input[type="checkbox"]:checked').each(function() {
			formState.extras.push($(this).val());
		});
	}

	/**
	 * Update order review
	 */
	function updateOrderReview() {
		let html = '<div class="review-grid">';
		
		// Customer info.
		html += '<div class="review-item">';
		html += '<span class="review-label">مشتری:</span>';
		if (formState.new_customer_data) {
			html += '<span class="review-value">' + formState.new_customer_data.name + ' (' + formState.new_customer_data.mobile + ')</span>';
		} else {
			html += '<span class="review-value">انتخاب شده</span>';
		}
		html += '</div>';

		html += '<div class="review-item">';
		html += '<span class="review-label">عنوان کتاب:</span>';
		html += '<span class="review-value">' + formState.book_title + '</span>';
		html += '</div>';

		html += '<div class="review-item">';
		html += '<span class="review-label">قطع:</span>';
		html += '<span class="review-value">' + formState.book_size + '</span>';
		html += '</div>';

		html += '<div class="review-item">';
		html += '<span class="review-label">نوع کاغذ:</span>';
		html += '<span class="review-value">' + formState.paper_type + ' - ' + formState.paper_weight + '</span>';
		html += '</div>';

		html += '<div class="review-item">';
		html += '<span class="review-label">نوع چاپ:</span>';
		html += '<span class="review-value">' + (formState.print_type === 'bw' ? 'سیاه و سفید' : 'رنگی') + '</span>';
		html += '</div>';

		html += '<div class="review-item">';
		html += '<span class="review-label">تعداد صفحات:</span>';
		html += '<span class="review-value">' + formState.page_count + '</span>';
		html += '</div>';

		html += '<div class="review-item">';
		html += '<span class="review-label">تیراژ:</span>';
		html += '<span class="review-value">' + formState.quantity + '</span>';
		html += '</div>';

		html += '<div class="review-item">';
		html += '<span class="review-label">نوع صحافی:</span>';
		html += '<span class="review-value">' + formState.binding_type + '</span>';
		html += '</div>';

		html += '<div class="review-item">';
		html += '<span class="review-label">گرماژ جلد:</span>';
		html += '<span class="review-value">' + formState.cover_weight + '</span>';
		html += '</div>';

		if (formState.extras.length > 0) {
			html += '<div class="review-item">';
			html += '<span class="review-label">خدمات اضافی:</span>';
			html += '<span class="review-value">' + formState.extras.join('، ') + '</span>';
			html += '</div>';
		}

		html += '</div>';

		$('#order_review_content').html(html);
	}

	/**
	 * Calculate price
	 */
	function calculatePrice() {
		const $button = $('#calculate_price_admin_btn');
		const $display = $('#price_display_admin');

		$button.prop('disabled', true).text(tabeshAdminOrderV2.strings.calculating);
		$display.html('<div class="price-loading">در حال محاسبه...</div>');

		$.ajax({
			url: tabeshAdminOrderV2.restUrl + '/calculate-price',
			method: 'POST',
			headers: {
				'X-WP-Nonce': tabeshAdminOrderV2.nonce
			},
			data: JSON.stringify({
				book_size: formState.book_size,
				paper_type: formState.paper_type,
				paper_weight: formState.paper_weight,
				print_type: formState.print_type,
				page_count: formState.page_count,
				quantity: formState.quantity,
				binding_type: formState.binding_type,
				cover_weight: formState.cover_weight,
				extras: formState.extras
			}),
			contentType: 'application/json',
			success: function(response) {
				if (response.success && response.price) {
					formState.calculated_price = response.price;
					
					let html = '<div class="price-result">';
					html += '<div class="price-row">';
					html += '<span class="price-label">قیمت هر جلد:</span>';
					html += '<span class="price-value">' + formatPrice(response.unit_price_tomans) + ' تومان</span>';
					html += '</div>';
					html += '<div class="price-row total">';
					html += '<span class="price-label">قیمت کل:</span>';
					html += '<span class="price-value">' + formatPrice(response.price.total_price_tomans) + ' تومان</span>';
					html += '</div>';
					html += '</div>';
					
					$display.html(html);
				} else {
					$display.html('<div class="price-error">خطا در محاسبه قیمت</div>');
				}
			},
			error: function(xhr) {
				let message = 'خطا در محاسبه قیمت';
				if (xhr.responseJSON && xhr.responseJSON.message) {
					message = xhr.responseJSON.message;
				}
				$display.html('<div class="price-error">' + message + '</div>');
			},
			complete: function() {
				$button.prop('disabled', false).text('محاسبه قیمت');
			}
		});
	}

	/**
	 * Handle submit
	 */
	function handleSubmit() {
		if (!validateStep(5)) {
			return;
		}

		const $button = $('#adminSubmitBtn');
		$button.prop('disabled', true).text(tabeshAdminOrderV2.strings.submitting);

		const orderData = {
			customer_id: formState.customer_id,
			book_title: formState.book_title,
			book_size: formState.book_size,
			paper_type: formState.paper_type,
			paper_weight: formState.paper_weight,
			print_type: formState.print_type,
			page_count: formState.page_count,
			quantity: formState.quantity,
			binding_type: formState.binding_type,
			cover_weight: formState.cover_weight,
			extras: formState.extras,
			notes: formState.notes,
			send_sms: formState.send_sms,
			calculated_price: formState.calculated_price
		};

		$.ajax({
			url: tabeshAdminOrderV2.restUrl + '/submit-order',
			method: 'POST',
			headers: {
				'X-WP-Nonce': tabeshAdminOrderV2.nonce
			},
			data: JSON.stringify(orderData),
			contentType: 'application/json',
			success: function(response) {
				if (response.success && response.order_id) {
					// Show success message.
					$('.wizard-form-wrapper').hide();
					$('.wizard-navigation').hide();
					$('#success_message_admin').show();
					
					// Update link.
					let linkHtml = '<a href="' + response.order_link + '" target="_blank">مشاهده سفارش #' + response.order_id + '</a>';
					$('#success_order_link').html(linkHtml);
					
					showToast(tabeshAdminOrderV2.strings.orderCreated, 'success');
				} else {
					showToast(response.message || tabeshAdminOrderV2.strings.error, 'error');
					$button.prop('disabled', false).text('ثبت سفارش');
				}
			},
			error: function(xhr) {
				let message = tabeshAdminOrderV2.strings.error;
				if (xhr.responseJSON && xhr.responseJSON.message) {
					message = xhr.responseJSON.message;
				}
				showToast(message, 'error');
				$button.prop('disabled', false).text('ثبت سفارش');
			}
		});
	}

	/**
	 * Reset form for new order
	 */
	function resetForm() {
		// Reset form state.
		formState.customer_id = null;
		formState.customer_type = 'existing';
		formState.new_customer_data = null;
		formState.book_title = '';
		formState.book_size = '';
		formState.paper_type = '';
		formState.paper_weight = '';
		formState.print_type = '';
		formState.page_count = 100;
		formState.quantity = 10;
		formState.binding_type = '';
		formState.cover_weight = '';
		formState.extras = [];
		formState.notes = '';
		formState.send_sms = false;
		formState.calculated_price = null;

		// Reset form fields.
		$('#tabesh-admin-wizard-form')[0].reset();
		$('#selected_customer_display').empty();
		$('#price_display_admin').empty();
		
		// Show form again.
		$('#success_message_admin').hide();
		$('.wizard-form-wrapper').show();
		$('.wizard-navigation').show();
		
		// Go to first step.
		showStep(1);
	}

	/**
	 * Show toast notification
	 */
	function showToast(message, type) {
		// Create toast element.
		const $toast = $('<div class="tabesh-toast ' + type + '">' + message + '</div>');
		$('body').append($toast);
		
		// Show toast.
		setTimeout(function() {
			$toast.addClass('show');
		}, 100);
		
		// Hide toast after 3 seconds.
		setTimeout(function() {
			$toast.removeClass('show');
			setTimeout(function() {
				$toast.remove();
			}, 300);
		}, 3000);
	}

	/**
	 * Format price with thousands separator
	 */
	function formatPrice(price) {
		return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

})(jQuery);
