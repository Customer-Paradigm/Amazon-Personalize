define([
	'ko',
    'uiComponent',
    'jquery'
], function (ko, Component, jQuery) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'CustomerParadigm_AmazonPersonalize/system/config/train_button',
			buttonMssg: "Start Process"
        },

        initialize: function () {
                this._super();
				this.setInitDisplay();
				this.steps = ko.observableArray([]);
				this.imgUrl = ko.observable('');
				this.mssg = ko.observable('');
				this.buttonStatus = ko.observable('');
				this.resetStatus = ko.observable('');
        },
		
		setInitDisplay: function(){
			var bttn = jQuery('#train_button > span');
			bttn.html(this.buttonMssg);
			this.mssg = ('Click button to kick off data creation process');
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
				jQuery.each(data.steps,function(idx,value){
		//			self.disableBttn(true);
					if(value.error) {
						imgUrl = self.errUrl;
						var html = '<p>Error in step ' + value.step_name + '</p>';
						html += '<p>' + value.mssg + '</p>';
						self.mssg(html);
						self.setBttnMssg("Processing Error");
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
					self.steps.push(value);
				});

				self.buttonStatus(self.processStatus);
			});
		},
		
		callReset: function(){
			self = this;
			var url = self.ajaxResetUrl;
			var imgUrl = self.successUrl;

			jQuery.getJSON(url, function(data) { 
				var imgUrl = '';
			});
		}
    });
});

