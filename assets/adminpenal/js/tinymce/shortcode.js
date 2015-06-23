(function() {
	tinymce.PluginManager.requireLangPack('yvii_shortcode');
	tinymce.create('tinymce.plugins.YVIIVideoPlugin', {
		init : function(ed, url) {
			
			// Register the command
			ed.addCommand('mceYVIIVideo', function() {
				// dialog window, set in assets/adminpenal/js/shortcode-modal.js
				if( YVIIVideo_DIALOG_WIN ){
					YVIIVideo_DIALOG_WIN.dialog('open');
				}	
			});

			// Register button
			ed.addButton('yvii_shortcode', {
				title : 'yvii_shortcode.title',
				cmd : 'mceYVIIVideo',
				class: 'YVII_dialog',
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
				longname 	: 'YouTube Videos Importer',
				author 		: 'YouTube Importer',
				authorurl 	: 'http://www.youtubeimporter.com',
				infourl 	: 'http://www.youtubeimporter.com',
				version 	: "1.0.1"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('yvii_shortcode', tinymce.plugins.YVIIVideoPlugin);
})();