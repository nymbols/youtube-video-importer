(function() {
	tinymce.PluginManager.requireLangPack('yytt_shortcode');
	tinymce.create('tinymce.plugins.YYTTVideoPlugin', {
		init : function(ed, url) {
			
			// Register the command
			ed.addCommand('mceYYTTVideo', function() {
				// dialog window, set in assets/back-end/js/shortcode-modal.js
				if( YYTTVideo_DIALOG_WIN ){
					YYTTVideo_DIALOG_WIN.dialog('open');
				}	
			});

			// Register button
			ed.addButton('yytt_shortcode', {
				title : 'yytt_shortcode.title',
				cmd : 'mceYYTTVideo',
				class: 'YYTT_dialog',
				url:'',
				image : url + '/images/ico.png'
			});

			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('example', n.nodeName == 'IMG');
			});
		},

		createControl : function(n, cm) {
			return null;
		},

		getInfo : function() {
			return {
				longname 	: 'YouTube Videos for WordPress',
				author 		: 'Constantin Boiangiu',
				authorurl 	: 'http://www.youtubeimporter.com',
				infourl 	: 'http://www.youtubeimporter.com',
				version 	: "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('yytt_shortcode', tinymce.plugins.YYTTVideoPlugin);
})();