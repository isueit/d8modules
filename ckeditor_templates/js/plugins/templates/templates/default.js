/*
 * @license Copyright (c) 2003-2019, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

var default_image = CKEDITOR.getUrl(CKEDITOR.plugins.getPath('templates')) + 'templates/generic.png';
// Register a templates definition set named "default".
CKEDITOR.addTemplates( 'default', {
	// The name of sub folder which hold the shortcut preview images of the
	// templates.
	imagesPath: CKEDITOR.getUrl(CKEDITOR.plugins.getPath('templates') + 'templates/images/'),
	// The templates definitions.
	templates: [ {
		title: 'Left Column Template',
		image: 'leftColumn.gif',
		description: 'Image to the left with text aligned to the right',
		html: 
			'<div class="row two-col-left">'+
				'<div class="col-md-3 col-sidebar">'+
					'<p>'+
						'<img height="200px" width="200px" style="float:left,padding:10px" alt="Placeholder Image" data-entity-type="file" data-entity-uuid="975b7a57-1a51-4e57-80d9-f570e6b72f3c" src='+default_image+'>'+
					'</p>'+
				'</div>'+
				'<div class ="col-md-9 col-main">'+
					'<h2>Left Column Template</h2>'+
						'<p>'+
							'This is the left column template.  Remove the placeholder text and image and replace with your own content.'+
						'</p>'+
						'<p>'+
							'This is a helpful way to insert image and text with appropriate padding and alignment properties.  If you wish to have two columns of text, try using the two column template provided instead.'+
						'</p>'+
				'</div>'+
			'</div>'
	},
	{
		title: 'Right Column Template',
		image: 'rightColumn.gif',
		description: 'Image to the right with text aligned to the left',
		html: 
		'<div class="row two-col-right">'+
				'<div class ="col-md-9 col-main">'+
					'<h2>Right Column Template</h2>'+
						'<p>'+
							'This is the Right column template.  Remove the placeholder text and image and replace with your own content.'+
						'</p>'+
						'<p>'+
							'This is a helpful way to insert image and text with appropriate padding and alignment properties.  If you wish to have two columns of text, try using the two column template provided instead.'+
						'</p>'+
				'</div>'+
				'<div class="col-md-3 col-sidebar">'+
					'<p>'+
						'<img height="200px" width="200px" style="float:right,padding:10px" alt="Placeholder Image" data-entity-type="file" data-entity-uuid="975b7a57-1a51-4e57-80d9-f570e6b72f3c" src='+default_image+'>'+
					'</p>'+
				'</div>'+
			'</div>'
	},
	{
		title: 'Two Column Template',
		image: 'twoColumn.gif',
		description: 'Two Columns of content',
		html:
		'<div class="row two-col">'+
			'<div class="col-md-6 col-1">'+
				'<h3>Two Column Template</h3>'+
				'<p>This is a two column template.  Remove the placeholder text and replace with your own content.</p>'+
				'<p>'+
					'This can be used for text or images.  The template provides the content in a responsive and accessible way.  If you wish to have an image in one column and text in the other, try using the left or right column templates provided instead.'+
				'</p>'+ 
			'</div>'+
			'<div class="col-md-6 col-2">'+
				'<h3>Two Column Template</h3>'+
				'<p>This is a two column template.  Remove the placeholder text and replace with your own content.</p>'+
				'<p>'+
					'This can be used for text or images.  The template provides the content in a responsive and accessible way.  If you wish to have an image in one column and text in the other, try using the left or right column templates provided instead.'+
				'</p>'+ 
			'</div>'+
		'</div>'
		}, 
	{
		title: 'Three Column Template',
		image: 'threeColumn.gif',
		description: 'Three Columns of content',
		html:
		'<div class="row three-col">'+
			'<div class="col-md-4 col-1">'+
				'<h3>Three Column Template</h3>'+
				'<p>This is a three column template.  Remove the placeholder text and replace with your own content.</p>'+
				'<p>'+
					'This can be used for text or images or a mixture of the two.  The template provides the content in a responsive and accessible way.'+
				'</p>'+ 
			'</div>'+
			'<div class="col-md-4 col-2">'+
				'<h3>Three Column Template</h3>'+
				'<p>This is a three column template.  Remove the placeholder text and replace with your own content.</p>'+
				'<p>'+
					'This can be used for text or images or a mixture of the two.  The template provides the content in a responsive and accessible way.'+
				'</p>'+ 
			'</div>'+
			'<div class="col-md-4 col-3">'+
				'<h3>Three Column Template</h3>'+
				'<p>This is a three column template.  Remove the placeholder text and replace with your own content.</p>'+
				'<p>'+
					'This can be used for text or images or a mixture of the two.  The template provides the content in a responsive and accessible way.'+
				'</p>'+ 
			'</div>'+
		'</div>'
		}
]
} );
