# SmugMug Media Type Module
This module provides a media type for remote SmugMug images

## Setup
This module uses the SmugMug API to get metadata for images, this requires a SmugMug API Key
The api key must be set on the Configuration > Media > SmugMug Settings page before creating SmugMug media

## Adding Images
Add images through the normal interface, adding the url and alt text

Example Urls

https://isuextensionimages.smugmug.com/Folder/i-FFFFFFF/A  
https://isuextensionimages.smugmug.com/Folder/i-FFFFFFF  
https://isuextensionimages.smugmug.com/Folder/Folder2/i-FFFFFFF  

The id for the image is extracted using a regex in [SmugmugAPI.php](src/Plugin/smugmug_media_type/Provider/SmugmugAPI.php), in the previous examples, the id is 'FFFFFFF'

## Image Styles
The Image FieldFormatter in this module automatically selects the image style from SmugMug that is equal to or greater than the scaling from the image style
Ex: The image style specifies 200x400, the selected image will be the one with the maximum dimension closest to 400px, the rendered element will be given the style tags max-width:200px and max-height:400px
This module requires a display type in order to get the image style, if it does not have a display for an image style, it will default to 220px by 220px, the default medium image style

## Adding Image Styles
Add image style as before (Configuration > Media > Image Styles)  
Add view mode as before (Structure > Display Modes > View Modes > Add View Mode) under Media  
Go to Admin > Structure > Media Types > Smugmug Image > Manage Display  
Click Custom display settings and check the new image style and click save  
Go to the tab for the new image style and click the gear beside the Image URL field  
Set the image style to the new image style  
Click update and click save

With the config module enabled (drush en config)  
Go to the Configuration > Development > Configuration Synchronization > Export page  
In order to use new image styles in another website, export the from the 'Entity view Display' configuration type with the configuration name 'Smugmug Image media items (media.smugmug.[new image style here])' config
'core.entity_view_display.media.smugmug.[image style here].yml' file and add it to the /config/install/ folder of this module  
The site 'filter.format.[editor name].yml' must be edited to allow new image styles to be selected in wysiwyg editor popup, this can be done for an individual site under Configuration > Content Authoring > Text Formats and Editors in the Embed Media tab of a given editor
