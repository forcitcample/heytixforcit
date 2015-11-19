<?php
return array(
	'name' => __('Google Maps', 'fitness-wellness') ,
	'desc' => __('In order to enable Google Map you need:<br>
                 Insert the Google Map element into the editor, open its option panel by clicking on the icon- edit on the right of the element and fill in all fields necessary.
' , 'fitness-wellness'),
		'icon' => array(
		'char' => WPV_Editor::get_icon('location1'),
		'size' => '26px',
		'lheight' => '39px',
		'family' => 'vamtam-editor-icomoon',
	),
	'value' => 'gmap',
	'controls' => 'size name clone edit delete',
	'options' => array(
		array(
			'name' => __('Address (optional)', 'fitness-wellness') ,
			'desc' => __('Unless you\'ve filled in the Latitude and Longitude options, please enter the address that you want to be shown on the map. If you encounter any errors about the maximum number of address translation requests per page, you should either use the latitude/longitude options or upgrade to the paid Google Maps API.', 'fitness-wellness'),
			'id' => 'address',
			'size' => 30,
			'default' => '',
			'type' => 'text',
		) ,
		array(
			'name' => __('Latitude', 'fitness-wellness') ,
			'desc' => __('This option is not necessary if an address is set.<br/><br/>', 'fitness-wellness'),
			'id' => 'latitude',
			'size' => 30,
			'default' => '',
			'type' => 'text',
		) ,
		array(
			'name' => __('Longitude', 'fitness-wellness') ,
			'desc' => __('This option is not necessary if an address is set.<br/><br/>', 'fitness-wellness'),
			'id' => 'longitude',
			'size' => 30,
			'default' => '',
			'type' => 'text',
		) ,
		array(
			'name' => __('Zoom', 'fitness-wellness') ,
			'desc' => __('Default map zoom level.<br/><br/>', 'fitness-wellness'),
			'id' => 'zoom',
			'default' => '14',
			'min' => 1,
			'max' => 19,
			'step' => '1',
			'type' => 'range'
		) ,
		array(
			'name' => __('Marker', 'fitness-wellness') ,
			'desc' => __('Enable an arrow pointing at the address.<br/><br/>', 'fitness-wellness'),
			'id' => 'marker',
			'default' => true,
			'type' => 'toggle'
		) ,
		array(
			'name' => __('HTML', 'fitness-wellness') ,
			'desc' => __('HTML code to be shown in a popup above the marker.<br/><br/>', 'fitness-wellness'),
			'id' => 'html',
			'size' => 30,
			'default' => '',
			'type' => 'text',
		) ,
		array(
			'name' => __('Popup Marker', 'fitness-wellness') ,
			'desc' => __('Enable to open the popup above the marker by default.<br/><br/>', 'fitness-wellness'),
			'id' => 'popup',
			'default' => false,
			'type' => 'toggle'
		) ,
		array(
			'name' => __('Controls (optional)', 'fitness-wellness') ,
			'desc' => sprintf(__('This option is intended to be used only by advanced users and is not necessary for most use cases. Please refer to the <a href="%s" title="Google Maps API documentation">API documentation</a> for details.', 'fitness-wellness'), 'https://developers.google.com/maps/documentation/javascript/controls'),
			'id' => 'controls',
			'size' => 30,
			'default' => '',
			'type' => 'text',
		) ,
		array(
			'name' => __('Scrollwheel', 'fitness-wellness') ,
			'id' => 'scrollwheel',
			'default' => false,
			'type' => 'toggle'
		) ,
		array(
			'name' => __('Maptype (optional)', 'fitness-wellness') ,
			'id' => 'maptype',
			'default' => 'ROADMAP',
			'options' => array(
				'ROADMAP' => __('Default road map', 'fitness-wellness') ,
				'SATELLITE' => __('Google Earth satellite', 'fitness-wellness') ,
				'HYBRID' => __('Mixture of normal and satellite', 'fitness-wellness') ,
				'TERRAIN' => __('Physical map', 'fitness-wellness') ,
			) ,
			'type' => 'select',
		) ,

		array(
			'name' => __('Color (optional)', 'fitness-wellness') ,
			'desc' => __('Defines the overall hue for the map. It is advisable that you avoid gray colors, as they are not well-supported by Google Maps.', 'fitness-wellness'),
			'id' => 'hue',
			'default' => '',
			'prompt' => __('Default', 'fitness-wellness') ,
			'options' => array(
				'accent1' => __('Accent 1', 'fitness-wellness'),
				'accent2' => __('Accent 2', 'fitness-wellness'),
				'accent3' => __('Accent 3', 'fitness-wellness'),
				'accent4' => __('Accent 4', 'fitness-wellness'),
				'accent5' => __('Accent 5', 'fitness-wellness'),
				'accent6' => __('Accent 6', 'fitness-wellness'),
				'accent7' => __('Accent 7', 'fitness-wellness'),
				'accent8' => __('Accent 8', 'fitness-wellness'),
			) ,
			'type' => 'select',
		) ,
		array(
			'name' => __('Width (optional)', 'fitness-wellness') ,
			'desc' => __('Set to 0 is the full width.<br/><br/>', 'fitness-wellness') ,
			'id' => 'width',
			'default' => 0,
			'min' => 0,
			'max' => 960,
			'step' => '1',
			'type' => 'range'
		) ,
		array(
			'name' => __('Height', 'fitness-wellness') ,
			'id' => 'height',
			'default' => '400',
			'min' => 0,
			'max' => 960,
			'step' => '1',
			'type' => 'range'
		) ,


		array(
			'name' => __('Title (optioanl)', 'fitness-wellness') ,
			'desc' => __('The title is placed just above the element.<br/><br/>', 'fitness-wellness'),
			'id' => 'column_title',
			'default' => '',
			'type' => 'text'
		) ,
		array(
			'name' => __('Title Type (optional)', 'fitness-wellness') ,
			'id' => 'column_title_type',
			'default' => 'single',
			'type' => 'select',
			'options' => array(
				'single' => __('Title with divider next to it', 'fitness-wellness'),
				'double' => __('Title with divider below', 'fitness-wellness'),
				'no-divider' => __('No Divider', 'fitness-wellness'),
			),
		) ,
	) ,
);