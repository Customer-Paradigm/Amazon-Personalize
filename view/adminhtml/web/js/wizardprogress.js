define([
	'ko',
	'uiComponent',
	'jquery'
], function (ko, Component, jQuery) {
	'use strict';

	return Component.extend({
		defaults: {
			template: 'CustomerParadigm_AmazonPersonalize/system/config/training_section',
			buttonMssg: "Start Process",
			initMssg: 'Click \'Start Process\' button to kick off data creation process'
		},

		initialize: function () {
			this._super();
			this.setInitDisplay();
			this.steps = ko.observableArray([]);
			this.imgUrl = ko.observable('');
			this.infoUrl = ko.observable(this.infoUrl);
			this.mssg = ko.observable(this.initMssg);
			this.buttonStatus = ko.observable('');
			this.resetStatus = ko.observable('');
			this.buttonError = ko.observable('');
		},

		setInitDisplay: function(){
			var bttn = jQuery('#training_section > span');
			bttn.html(this.buttonMssg);
		},

		startProcess: function() {
			this.setBttnMssg("In Progress");
			this.showProgress(true);
		},

		setBttnMssg: function(txt) {
			var bttn = jQuery('#train_button');
			bttn.find('span').html(txt);
		},

		disableBttn: function(disabled) {
			var bttn = jQuery('#train_button');
			bttn.prop('disabled', disabled);
		},

		displayErrorMssg: function() {
			var mssg = jQuery('#train_message_wrapper');
			mssg.css('display', 'block');
		},

		closeErrorMssg: function() {
			var mssg = jQuery('#train_message_wrapper');
			mssg.css('display', 'none');
		},

		displayErrorBttn: function(displaytype) {
			var bttn = jQuery('#error_button');
			bttn.css('display', displaytype);
		},

		displayRstBttn: function(displaytype) {
			var bttn = jQuery('#reset_button');
			bttn.css('display', displaytype);
		},

		resetProcess: function() {
			this.setRstBttnMssg("Resetting");
			this.callReset();
		},

		setRstBttnMssg: function(txt) {
			var bttn = jQuery('#reset_button');
			bttn.find('span').html(txt);
		},

		showProgress: function(startProcess){
			self = this;
			var url = self.ajaxDisplayUrl;
			var imgUrl = self.successUrl;
			var infoUrl = self.infoUrl;
			/* TODO -- debug */
			//self.displayRstBttn('none');
			self.displayRstBttn('block');

			if(typeof startProcess !== "undefined") { 
				url = self.ajaxRunUrl; 
				// clear steps
				self.steps([]);
				self.disableBttn(true);
			}

			jQuery.getJSON(url, function(data) { 
				var imgUrl = '';
				var infoUrl = '';
				jQuery.each(data.steps,function(idx,value){
					//			self.disableBttn(true);
					if(value.error) {
						imgUrl = self.errUrl;
						infoUrl = self.infoUrl;
						var html = '<div class="error-message-header"> Error in step ' + value.step_name + '</div>';
						if(value.mssg.includes("you need at least 1000")) {
							self.setBttnMssg("Recheck Interactions");
							html += '<div class="error-message-body">';
							html += '<div>You need at least 1000 interactions to train your model</div>';
							html += '<span>Details: </span>';
							html += '<a href="https://docs.aws.amazon.com/personalize/latest/dg/limits.html#limits-table">Amazon Service quotas</a>';
							html += '</div>';
						} else {
							self.setBttnMssg("Processing Error");
							html += '<div class="error-message-body">' + value.mssg + '</div>';
						}
						self.mssg(html);
						self.displayErrorBttn('block');
						self.displayRstBttn('block');
					} else if(value.state == 'not started') {
						imgUrl = self.pendingUrl;
					} else if(value.state == 'in progress') {
						self.buttonStatus('inProgress');
						self.setBttnMssg("In Progress");
						imgUrl = self.processingUrl;
					} else if(self.processStatus == 'ready') {
						self.buttonStatus('inProgress');
						self.setBttnMssg("In Progress");
						imgUrl = self.successUrl;
					} else if(self.processStatus == "finished") {
						self.setBttnMssg("Finished");
						imgUrl = self.successUrl;
						self.displayRstBttn('block');
						self.steps([]);
						return false;
					} else {
						imgUrl = self.successUrl;
					}

					value.imgUrl = imgUrl;
					value.infoUrl = infoUrl;
					self.steps.push(value);
				});

				self.buttonStatus(self.processStatus);
			});
		},

		callReset: function(){
			self = this;
			var url = self.ajaxResetUrl;
			var imgUrl = self.successUrl;
			var infoUrl = self.infoUrl;

			jQuery.getJSON(url, function(data) { 
				var imgUrl = '';
				var infoUrl = '';
			});
		}
	});
});

