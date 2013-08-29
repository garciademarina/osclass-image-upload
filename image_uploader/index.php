<?php
/*
Plugin Name: Image Uploader
Plugin URI: http://www.osclass.org/
Description: This plugin allow to use fine uploader at add and edit listing pages
Version: 0.1
Author: Osclass
Short Name: image_uploader
Author URI: http://www.osclass.org/
Plugin update URI:
*/
    function print_image_uploader()
    {
        $aImages = array();
        if( Session::newInstance()->_getForm('fu_pre_images') != '' ) {
            $aImages = Session::newInstance()->_getForm('fu_pre_images');
        }
        ?>
        <div id="restricted-fine-uploader"></div>

        <?php if(count($aImages)>0) { ?>
        <ul class="qq-upload-list">
        <?php foreach($aImages as $img){ ?>
            <li class=" qq-upload-success">
                <span class="qq-upload-file">b1goat001.jpg</span>
                <a class="qq-upload-delete" onclick="$(this).parent().remove();" style="display: inline; cursor:pointer;">Delete</a>
                <div class="fu_preview_img"><img src="oc-content/uploads/temp/<?php echo osc_esc_html($img); ?>" alt="<?php echo osc_esc_html($img); ?>"></div>
                <input type="hidden" name="fu_images[]" value="<?php echo osc_esc_html($img); ?>">
            </li>
        <?php } ?>
        </ul>
        <?php }
    }

    function fu_clear_session()
    {
        // clear session
        Session::newInstance()->_clearVariables();
    }

    function fu_header_script()
    {
        // <div id="restricted-fine-uploader"></div>
        $aExt = explode(',',osc_allowed_extension());
        foreach($aExt as $key => $value)
            $aExt[$key] = "'".$value."'";
        $allowedExtensions = join(',', $aExt);

        $maxSize = (int)osc_max_size_kb()*1024;

        ?>

        <script>
        $(document).ready(function() {
            $('#restricted-fine-uploader').fineUploader({
                request: {
                    endpoint: '<?php echo osc_ajax_plugin_url(osc_plugin_folder(__FILE__).'server/upload.php'); ?>'
                },
                multiple: true,
                validation: {
                    allowedExtensions: [<?php echo $allowedExtensions; ?>], //['jpeg', 'jpg', 'gif', 'png'],
                    sizeLimit: <?php echo $maxSize; ?> // 50 kB = 50 * 1024 bytes
                },
                // optional feature
                deleteFile: {
                    enabled: true,
                    method: "POST",
                    endpoint: '<?php echo osc_ajax_plugin_url(osc_plugin_folder(__FILE__).'server/success.php'); ?>'
                },
                text: {
                    uploadButton: 'Click or Drop for upload images'
                },
                showMessage: function(message) {
                    // Using Bootstrap's classes
                    $('#restricted-fine-uploader').append('<div class="alert alert-error">' + message + '</div>');
                }
                }).on('complete', function(event, id, fileName, responseJSON) {
                    if (responseJSON.success) {
                        var li = $('.qq-upload-list li')[id];
                        $(li).append('<div class="fu_preview_img"><img src="oc-content/uploads/temp/'+responseJSON.uploadName+'" alt="' + fileName + '"></div>');
                        $(li).append('<input type="hidden" name="fu_images[]" value="'+responseJSON.uploadName+'"></input>'); //uploadName
                    }
                });
        });
        </script>
        <?php
    }




    function fu_add_image_resources($item)
    {

        $itemId = $item['pk_i_id'];

        $itemResourceManager = ItemResource::newInstance();
        $aImages = Params::getParam('fu_images');
        foreach($aImages as $img) {

            $tmpName = osc_uploads_path().'/temp/'.$img;

            $total_size = 0;

            // Create normal size
            $normal_path = $path = $tmpName."_normal";
            $size = explode('x', osc_normal_dimensions());
            ImageResizer::fromFile($tmpName)->resizeTo($size[0], $size[1])->saveToFile($path);

            if( osc_is_watermark_text() ) {
                $wat->doWatermarkText( $path , osc_watermark_text_color(), osc_watermark_text() , 'image/jpeg' );
            } elseif ( osc_is_watermark_image() ){
                $wat->doWatermarkImage( $path, 'image/jpeg');
            }

            // Create preview
            $path = $tmpName."_preview";
            $size = explode('x', osc_preview_dimensions());
            ImageResizer::fromFile($normal_path)->resizeTo($size[0], $size[1])->saveToFile($path);

            // Create thumbnail
            $path = $tmpName."_thumbnail";
            $size = explode('x', osc_thumbnail_dimensions());
            ImageResizer::fromFile($normal_path)->resizeTo($size[0], $size[1])->saveToFile($path);

            $itemResourceManager->insert(array(
                'fk_i_item_id' => $itemId
            ));
            $resourceId = $itemResourceManager->dao->insertedId();

            osc_copy($tmpName.'_normal', osc_uploads_path() . $resourceId . '.jpg');
            osc_copy($tmpName.'_preview', osc_uploads_path() . $resourceId . '_preview.jpg');
            osc_copy($tmpName.'_thumbnail', osc_uploads_path() . $resourceId . '_thumbnail.jpg');
            if( osc_keep_original_image() ) {
                $path = osc_uploads_path() . $resourceId.'_original.jpg';
                move_uploaded_file($tmpName, $path);
            }

            $s_path = str_replace(osc_base_path(), '', osc_uploads_path());
            $resourceType = 'image/jpeg';
            $itemResourceManager->update(
                array(
                    's_path'          => $s_path
                    ,'s_name'         => osc_genRandomPassword()
                    ,'s_extension'    => 'jpg'
                    ,'s_content_type' => $resourceType
                )
                ,array(
                    'pk_i_id'       => $resourceId
                    ,'fk_i_item_id' => $itemId
                )
            );
            osc_run_hook('uploaded_file', ItemResource::newInstance()->findByPrimaryKey($resourceId));
        }
    }

    /*
     * save uploaded images into session
     */
    function fu_save_images_session()
    {
        Session::newInstance()->_setForm('fu_pre_images'      , Params::getParam('fu_images') );
        Session::newInstance()->_keepForm('fu_pre_images');
    }

    /*
     * Load css and js
     * - add clear session at footer hook
     */
    function fu_init()
    {
        $page   = Rewrite::newInstance()->get_location();
        $action = Rewrite::newInstance()->get_section();

        if($page=="item" && ($action=="item_add" || $action=="item_edit") ) {

            osc_add_hook('footer','fu_header_script');

            // js and css
            osc_enqueue_style('fu-fine-uploader-css', osc_base_url() . 'oc-content/plugins/fine_uploader/jquery.fineuploader/fineuploader.css');
            osc_enqueue_style('fu-fine-uploader-custom-css', osc_base_url() . 'oc-content/plugins/fine_uploader/css/custom.css');

            osc_register_script('fu-fine-uploader-js', osc_base_url() . 'oc-content/plugins/fine_uploader/jquery.fineuploader/jquery.fineuploader-3.8.0.min.js', 'jquery');
            osc_enqueue_script('fu-fine-uploader-js');

            // clear session
            osc_add_hook("footer", 'fu_clear_session', 9);
        }
    }

    osc_add_hook('init', 'fu_init');

    osc_add_hook('pre_item_add',  'fu_save_images_session' );
    osc_add_hook('pre_item_edit', 'fu_save_images_session' );

    osc_add_hook('posted_item', 'fu_add_image_resources');
    osc_add_hook('edited_item', 'fu_add_image_resources');

    function fu_install()
    {
        // create temp directory
        $pathname = osc_uploads_path().'/temp';
        @mkdir($pathname);
    }

    function fu_uninstall()
    {
        // remove temp directory
        $dirname = osc_uploads_path().'/temp';
        @rmdir($dirname);
    }

    osc_register_plugin(osc_plugin_path(__FILE__), 'fu_install' );
    osc_add_hook(osc_plugin_path(__FILE__)."_uninstall", 'fu_uninstall' );

    /**
     * Remove files > 1h
     */
    function fu_cron_hourly() {

        $dir = osc_uploads_path() . '/temp';
        if ($manager = opendir($dir)) {
            while (false !== ($entrada = readdir($manager))) {
                if ($entrada != "." && $entrada != "..") {
                    $filename = $dir . $entrada;
                    $now = new DateTime();
                    $d = new DateTime( );
                    $d->setTimestamp(filectime($filename));
                    $diff = $d->diff($now);

                    $min = $diff->i;
                    $min = $min + ($diff->h * 60);
                    $min = $min + ($diff->d * 24 * 60);

                    // more than 15 min remove temp image
                    if ($min >= 60) {
                        @unlink($filename);
                    }
                }
            }
            closedir($manager);
        }
    }
    osc_add_hook('cron_hourly', 'fu_cron_hourly');
?>