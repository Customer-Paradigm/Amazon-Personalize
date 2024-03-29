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
			initMssg: 'Click \'Start Process\' button to kick off data creation process',
			resumeProcessMssg: "Make sure cron is running and starts the campaign creation where it left off",
			resetAllMssg: "Removes all imported data, data group, trained campaign, and event tracker for this website from AWS.<br>Charges will apply if you wish to recreate a campaign.<a href='https://aws.amazon.com/personalize/pricing' target='_blank'> View pricing info</a>",
			resetCampMssg: "Removes only trained campaign and event tracker for this website from AWS.<br>Charges will apply if you wish to recreate a campaign. <a href='https://aws.amazon.com/personalize/pricing' target='_blank'> View pricing info</a>"
		},

		initialize: function () {
			this._super();
			this.steps = ko.observableArray([]);
			this.imgUrl = ko.observable('');
			this.infoUrl = ko.observable(this.infoUrl);
			this.mssg = ko.observable(this.initMssg);
			this.rsmProcessMssg = ko.observable(this.resumeProcessMssg);
			this.rstAllMssg = ko.observable(this.resetAllMssg);
			this.rstCampMssg = ko.observable(this.resetCampMssg);
			this.errlog = ko.observable('testing error log');
			this.buttonStatus = ko.observable('');
			this.resumeProcessStatus = ko.observable('');
			this.resetStatus = ko.observable('');
			this.resetCampStatus = ko.observable('');
			this.buttonError = ko.observable('');
		},

		setInitDisplay: function(){
			this.setBttnMssg("Start");
		},

		startProcess: function() {
			this.setBttnMssg("In Progress");
			this.showProgress(true);
			this.initAutoReload();
		},

		initAutoReload: function() {
			setInterval(() => this.showProgress(false), 10000);
		},

		setBttnMssg: function(txt, el = '#train_button') {
			var bttn = jQuery(el);
			bttn.find('span').html(txt);
		},

		disableTrainBttn: function(disabled) {
			var bttn = jQuery('#train_button');
			bttn.prop('disabled', disabled);
		},
		
		hideTrainBttn: function() {
			var bttn = jQuery('#train_button');
			bttn.css('display', 'none');
		},
		
		showTrainBttn: function() {
			var bttn = jQuery('#train_button');
			bttn.css('display', 'block');
		},

		displayErrorMssg: function() {
			var mssg = jQuery('#train_message_wrapper');
			mssg.css('display', 'block');
		},
		
		displayRstAllMssg: function() {
			var mssg = jQuery('#reset_all_message_wrapper');
			mssg.css('display', 'block');
		},

		displayRstAllMssg: function() {
			var mssg = jQuery('#resume_process_message_wrapper');
			mssg.css('display', 'block');
		},
		
		displayRstCampMssg: function() {
			var mssg = jQuery('#reset_camp_message_wrapper');
			mssg.css('display', 'block');
		},

		closeErrorMssg: function() {
			var mssg = jQuery('#train_message_wrapper');
			mssg.css('display', 'none');
			location.reload();
			return false;
		},
		
		closeRstAllMssg: function() {
			var mssg = jQuery('#reset_all_message_wrapper');
			mssg.css('display', 'none');
		},
		
		closeRstCampMssg: function() {
			var mssg = jQuery('#reset_camp_message_wrapper');
			mssg.css('display', 'none');
		},

		displayAssets: function() {
			var field = jQuery("#row_awsp_settings_awsp_assets_asset_display > td.value > p");
			var url = self.ajaxAssetDisplayUrl;
			var link = '<a id="asset_download_link" href="' + self.ajaxErrorlogDownloadUrl + '"><span>Download</span> </a>'
			jQuery.getJSON(url, function(data) { 
				var html = '<table id="asset_display_table">';
				// Header
				   html += '<tr class="asset-header">';
					html += '<td>Name</td>';
					html += '<td>Config Path</td>';
					html += '<td>Value</td>';
				   html += '</tr>';
				// Rows
				data.each(function( item ) {
				   html += '<tr class="asset-row">';
					html += '<td class="aName-cell">' + item.name + '</td>';
					html += '<td class="aPath-cell">' + item.path + '</td>';
					html += '<td class="aValue-cell">' + item.value + '</td>';
				   html += '</tr>';
				});
				html += '</table>';
				field.html(link);
				field.append(html);
			});
		},

		displayErrorLog: function() {
			var field = jQuery("#row_awsp_settings_awsp_logs_log_display > td.value > p");
			var url = self.ajaxErrorlogUrl;
			var link = '<a id="errorlog_download_link" href="' + self.ajaxErrorlogDownloadUrl + '"><span>Download</span> </a>'
			jQuery.getJSON(url, function(data) { 
				var hasItems = false;
				var html = '<table id="error_log_table">';
				data.each(function( item ) {
				   html += '<tr>';
					html += "<td>" + JSON.stringify(item).replace(/^"|"$/g, ''); + '</td>';
				   html += '</tr>';
				   hasItems = true;
				});
				html += '</table>';
				if( hasItems) {
				field.html(link);
				}
				field.append(html);
			});
		},
		
		closeErrorLog: function() {
			var mssg = jQuery('#error_log_wrapper');
			mssg.css('display', 'none');
		},

		displayErrorBttn: function(displaytype) {
			var bttn = jQuery('#error_button');
			bttn.css('display', displaytype);
		},

		displayAdvSection: function(displaytype) {
			var section = jQuery('#row_awsp_settings_awsp_training_awsp_training_advanced');
			section.css('display', displaytype);
		},

		displayRstBttn: function(displaytype) {
			var bttn = jQuery('#reset_button');
			var bttnimg = jQuery('#rst_bttn_info');
			bttn.css('display', displaytype);
			bttnimg.css('display', displaytype);
		},
		
		displayRstCampaignBttn: function(displaytype) {
			var bttn = jQuery('#reset_campaign_button');
			var bttnimg = jQuery('#rst_cmpn_bttn_info');
			bttn.css('display', displaytype);
			bttnimg.css('display', displaytype);
		},

		resumeProcess: function() {
			if (confirm('Resume campaign training process?')) {
				this.setBttnMssg("Resuming campaign", '#resume_process_button');
				this.callCampResume();
			}else {
				// nada
			}
		},

		resetProcess: function() {
			self = this;
			var imgUrl = self.successUrl;
			var infoUrl = self.infoUrl;
			if (confirm('Are you sure? This will remove your running campaign and ALL associated data for this website from AWS Personalize.')) {
				this.setBttnMssg("Removing all", '#reset_button');
				this.callReset();
			}else {
				// nada
			}
		},
		
		resetCampaign: function() {
			if (confirm('Are you sure? This will remove only the current campaign and event tracker, but there will be charges if you wish to retrain a campaign.')) {
				this.setBttnMssg("Removing campaign", '#reset_campaign_button');
				this.callCampReset();
			}else {
				// nada
			}
		},

		setRstBttnMssg: function(txt) {
			var bttn = jQuery('#reset_button');
			bttn.find('span').html(txt);
		},

		hideGauge: function() {
			var gauge = jQuery('.interaction-wrapper');
			gauge.hide()
		},
		
		showGauge: function() {
			var gauge = jQuery('.interaction-wrapper');
			gauge.show()
		},

		displayGauge: function() {
			var gauge = jQuery('#interaction-count');
			var number = jQuery('#interaction-number');
			var url = self.ajaxInteractionUrl;
			jQuery.getJSON(url, function(data) { 
				if( data.paused ) {
				//	self.showProgress();
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
			jQuery("#loader").show();
			self = this;
//			self.displayLicenseStatus();
			var url = self.ajaxDisplayUrl;
			var imgUrl = self.successUrl;
			var infoUrl = self.infoUrl;

			if(typeof startProcess !== "undefined") { 
				url = self.ajaxRunUrl; 
				// clear steps
				self.steps([]);
				self.disableTrainBttn(true);
			}
			self.hideGauge();
			self.displayAssets();
			self.displayErrorLog();

			jQuery.getJSON(url).done( function(data) { 
				var imgUrl = '';
				var infoUrl = '';
				self.steps([]);
				self.showTrainBttn();
				jQuery.each(data.steps,function(idx,value){
					if(value.error) {
						self.disableTrainBttn(true);
						imgUrl = self.errUrl;
						infoUrl = self.infoUrl;
						var html = '<div class="error-message-header"> Error in step ' + value.step_name + '</div>';
						var isCsvCreate = value.step_name == "Create Csv Files";
						if(isCsvCreate && self.needsInteractions) {
							self.setBttnMssg("Paused: Generating Interactions");
							html += '<div class="error-message-body">';
							html += '<div>You need at least 1000 unique interactions to train your model.</div>';
							html += '<div>The Interactions Progress Gauge is tracking customer interactions on your site and will resume the traing process when you have enough interactions.</div>';
							html += '<span>Details: </span>';
							html += '<a href="https://docs.aws.amazon.com/personalize/latest/dg/limits.html#limits-table">Amazon Service quotas</a>';
							html += '</div>';
							self.displayGauge();
							self.showGauge();
						} else {
							self.setBttnMssg("Processing Error");
							html += '<div class="error-message-body">' + value.mssg + '</div>';
						}
						self.mssg(html);
						self.displayErrorBttn('block');
						self.displayAdvSection('block');
						self.displayRstBttn('block');
						self.displayRstCampaignBttn('block');
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
						self.disableTrainBttn(true);
						self.setBttnMssg("Campaign Created");
						imgUrl = self.successUrl;
						return false;
					} else {
						imgUrl = self.successUrl;
					}

					value.imgUrl = imgUrl;
					value.infoUrl = infoUrl;
					self.steps.push(value);
				});
				

				self.buttonStatus(self.processStatus);
				return true
			})
			.always(function () { 
				jQuery("#loader").hide(); 
			});
		},

		callResume: function(){
			self = this;
			var url = self.ajaxResumeUrl;
			var imgUrl = self.successUrl;
			var infoUrl = self.infoUrl;

			jQuery.getJSON(url, function(data) { 
				var imgUrl = '';
				var infoUrl = '';
			});
			return true;
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
			return true;
		},
		
		callCampReset: function(){
			self = this;
			var url = self.ajaxResetCampUrl;
			var imgUrl = self.successUrl;
			var infoUrl = self.infoUrl;

			jQuery.getJSON(url, function(data) { 
				var imgUrl = '';
				var infoUrl = '';
			});
			return true;
		}
	});
});

