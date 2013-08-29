# image uploader plugin

Compatible with Osclass version 3.1 and up.
Osclass plugin that improve upload image process.

Replace the upload process and use javascript library fine uploader and jquery.

## Features

  * Multiple file upload.
  
  * Drag & drop files.
  
  * Image preview. 
  
  * Daily purge of old temporary images (Cron) 
  
## Preview

![alt text](http://i.imgur.com/KceFIN0.png "Image upload osclass")

## Usage

Once plugin is installed, you need to add following code to item-post.php theme
file, under the folder oc-content/themes/THEME_NAME.

* ***Find and remove all code related to image upload action, using Bender theme for example:***

```
<?php if(osc_images_enabled_at_items()) ItemForm::photos_javascript(); ?>
```


```php
<?php if( osc_images_enabled_at_items() ) { ?>
<div class="box photos">
    <h2><?php _e('Photos', 'bender'); ?></h2>
    <div class="control-group">
        <label class="control-label" for="photos[]"><?php _e('Photos', 'bender'); ?></label>
        <div class="controls">
            <div id="photos">
                <?php ItemForm::photos(); ?>
            </div>
        </div>
        <div class="controls">
            <a href="#" onclick="addNewPhoto(); return false;"><?php _e('Add new photo', 'bender'); ?></a>
        </div>
    </div>
</div>
<?php } ?>
```

* ***Add as replacement this code***

```php
<?php if( osc_images_enabled_at_items() ) { ?>
<div class="box photos">
    <h2><?php _e('Photos', 'bender'); ?></h2>
    <div class="control-group">
        <?php ItemForm::photos(); ?>
        <?php print_image_uploader(); ?>
    </div>
    <div style="clear:both;"></div>
    <br/>
</div>
<?php } ?>

```


* **Note** *Most likely you will need to edit styles to integrate the plugin with your theme, now its compatible with default theme Bender*


 
