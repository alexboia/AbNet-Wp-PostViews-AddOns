(function () {
	'use strict';

	var root = document.querySelector('[data-abnet-honey-trap]');
	if (!root || typeof abnetHoneyTrapAdmin === 'undefined') {
		return;
	}

	var layout = root.querySelector('[data-abnet-master-detail-layout]');
	var master = root.querySelector('[data-abnet-master-detail-master]');
	var detail = root.querySelector('[data-abnet-master-detail-detail]');
	var modal = root.querySelector('[data-abnet-admin-modal]');
	var modalContent = root.querySelector('[data-abnet-admin-modal-content]');
	var selectedGroup = null;
	var lastModalTrigger = null;

	function loadingMarkup() {
		return '<div class="abnet-honey-trap-loading"><span class="spinner is-active"></span></div>';
	}

	function request(action, data) {
		var body = new URLSearchParams(Object.assign({
			action: action,
			nonce: abnetHoneyTrapAdmin.nonce
		}, data || {}));

		return fetch(abnetHoneyTrapAdmin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
			body: body.toString()
		}).then(function (response) {
			return response.json();
		}).then(function (response) {
			if (!response.success || !response.data || typeof response.data.html !== 'string') {
				throw new Error(response.data && response.data.message ? response.data.message : abnetHoneyTrapAdmin.errorMessage);
			}
			return response.data.html;
		});
	}

	function showError(container, error) {
		container.textContent = error.message || abnetHoneyTrapAdmin.errorMessage;
		container.classList.add('notice', 'notice-error');
	}

	function loadMaster(page) {
		master.classList.remove('notice', 'notice-error');
		master.innerHTML = loadingMarkup();
		request(abnetHoneyTrapAdmin.actions.master, {page: page}).then(function (html) {
			master.innerHTML = html;
		}).catch(function (error) {
			showError(master, error);
		});
	}

	function loadDetail(page) {
		if (!selectedGroup) {
			return;
		}
		detail.hidden = false;
		layout.classList.add('has-detail');
		detail.innerHTML = loadingMarkup();
		request(abnetHoneyTrapAdmin.actions.detail, {
			page: page,
			signature: selectedGroup.signature,
			trapType: selectedGroup.trapType
		}).then(function (html) {
			detail.innerHTML = html;
		}).catch(function (error) {
			showError(detail, error);
		});
	}

	function closeDetail() {
		selectedGroup = null;
		detail.hidden = true;
		detail.innerHTML = '';
		layout.classList.remove('has-detail');
	}

	function openIpAddresses(button) {
		lastModalTrigger = button;
		modal.hidden = false;
		modalContent.innerHTML = loadingMarkup();
		document.body.classList.add('modal-open');
		request(abnetHoneyTrapAdmin.actions.ipAddresses, {
			signature: button.dataset.signature
		}).then(function (html) {
			modalContent.innerHTML = html;
			root.querySelector('[data-abnet-modal-close-button]').focus();
		}).catch(function (error) {
			showError(modalContent, error);
		});
	}

	function closeModal() {
		modal.hidden = true;
		modalContent.innerHTML = '';
		document.body.classList.remove('modal-open');
		if (lastModalTrigger) {
			lastModalTrigger.focus();
		}
	}

	root.addEventListener('click', function (event) {
		if (event.target.matches('[data-abnet-modal-close]')) {
			closeModal();
			return;
		}

		var target = event.target.closest('button');
		if (!target) {
			return;
		}
		if (target.matches('[data-master-page]')) {
			loadMaster(target.dataset.masterPage);
		} else if (target.matches('[data-view-bots]')) {
			selectedGroup = {signature: target.dataset.signature, trapType: target.dataset.trapType};
			loadDetail(1);
		} else if (target.matches('[data-detail-page]')) {
			loadDetail(target.dataset.detailPage);
		} else if (target.matches('[data-close-detail]')) {
			closeDetail();
		} else if (target.matches('[data-view-ips]')) {
			openIpAddresses(target);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && !modal.hidden) {
			closeModal();
		}
	});

	loadMaster(1);
}());
