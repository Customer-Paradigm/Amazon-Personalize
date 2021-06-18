define([
	'ko',
	'uiComponent',
	'jquery'
], function (ko, Component, jQuery) {
	'use strict';

	return Component.extend({
		defaults: {
			template: 'CustomerParadigm_AmazonPersonalize/system/config/training_section',
			buttonMssg: "Start Process---",
			initMssg: 'Click \'Start Process\' button to kick off data creation process'
		},

		initialize: function () {
			this._super();
			this.steps = ko.observableArray([]);
			this.imgUrl = ko.observable('');
			this.infoUrl = ko.observable(this.infoUrl);
			this.mssg = ko.observable(this.initMssg);
			this.buttonStatus = ko.observable('');
			this.resetStatus = ko.observable('');
			this.buttonError = ko.observable('');
		},

		setInitDisplay: function(){
			this.setBttnMssg("Start");
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

		displayGauge: function() {
			var gauge = jQuery('#interaction-count');
			var number = jQuery('#interaction-number');
			var url = self.ajaxInteractionUrl;
			jQuery.getJSON(url, function(data) { 
				console.log(data);
				if( data.paused ) {
					self.showProgress();
					var pct = (data.value / 1000) * 100;
					gauge.css('width', pct + '%');
					if(number[0]) {
						number[0].innerText = data.value + " of 1000";
					}
				} else {
					return true;
				}
			});
		},

		showProgress: function(startProcess){
			self = this;
			console.log('interactions');
			console.log(self.interactionsCount);
			console.log(self.needsInteractions);
			var url = self.ajaxDisplayUrl;
			var imgUrl = self.successUrl;
			var infoUrl = self.infoUrl;
			if(self.needsInteractions) {
				self.displayGauge();
			}
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
					console.log(data.steps);
				var imgUrl = '';
				var infoUrl = '';
				jQuery.each(data.steps,function(idx,value){
					console.log(value);
					if(value.error) {
						self.disableBttn(true);
						imgUrl = self.errUrl;
						infoUrl = self.infoUrl;
						var html = '<div class="error-message-header"> Error in step ' + value.step_name + '</div>';
						//if(value.mssg.includes("You need at least 1000")) {
						if(self.needsInteractions) {
							self.setBttnMssg("Paused: Generating Interactions");
							html += '<div class="error-message-body">';
							html += '<div>You need at least 1000 interactions to train your model.</div>';
							html += '<div>The Interactions Progress Guage is tracking customer interactions on your site and will resume the traing process when you have enough interactions.</div>';
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

