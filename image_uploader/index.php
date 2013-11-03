<?php
/*
Plugin Name: Image Uploader
Plugin URI: http://www.osclass.org/
Description: This plugin allow to use fine uploader at add and edit listing pages
Version: 0.9
Author: Osclass
Short Name: image_uploader
Author URI: http://www.osclass.org/
Plugin update URI:
*/

require_once PLUGINS_PATH.'image_uploader/server/qqFileUploader.php';

    function print_image_uploader()
    {
        $aImages = array();
        if( Session::newInstance()->_getForm('fu_pre_images') != '' ) {
            $aImages = Session::newInstance()->_getForm('fu_pre_images');
            Session::newInstance()->_drop('fu_pre_images');
            Session::newInstance()->_dropKeepForm('fu_pre_images');
        }
        ?>
        <div id="restricted-fine-uploader"></div>

        <div style="clear:both;"></div>
        <?php if(count($aImages)>0) { ?>
        <br/>
        <h3><?php _e('Images already uploaded', 'image_uploader');?></h3>
        <ul class="qq-upload-list">
        <?php foreach($aImages as $img){ ?>
            <li class=" qq-upload-success">
                <span class="qq-upload-file"><?php echo $img; ?></span>
                <a class="qq-upload-delete" onclick="$(this).parent().remove();" style="display: inline; cursor:pointer;">Delete</a>
                <div class="fu_preview_img"><img src="oc-content/uploads/temp/<?php echo osc_esc_html($img); ?>" alt="<?php echo osc_esc_html($img); ?>"></div>
                <input type="hidden" name="fu_images[]" value="<?php echo osc_esc_html($img); ?>">
            </li>
        <?php } ?>
        </ul>
        <?php } ?>
        <div style="clear:both;"></div>
        <?php
    }

    function ajax_fu_validate()
    {
        $id = Params::getParam('id');
        if(!is_numeric($id)) { echo json_encode(array('success' => false)); die();}

        $secret = Params::getParam('secret');
        $item = Item::newInstance()->findByPrimaryKey($id);
        if($item['s_secret']!=$secret) { echo json_encode(array('success' => false)); die();}

        $result = array('success' => true);
        $nResources = ItemResource::newInstance()->countResources($id);

        if($nResources>=osc_max_images_per_item()) {
            $result = array('success' => false, 'count' => $nResources);
        } else {
            $result = array('success' => true, 'count' => $nResources);
        }
        // to pass data through iframe you will need to encode all html tags
        echo json_encode($result);

    }
    osc_add_hook('ajax_fu_validate', 'ajax_fu_validate');

    function ajax_fu_upload()
    {
        // Include the uploader class

        // <div id="restricted-fine-uploader"></div>
        $allowedExtensions =  explode(',',osc_allowed_extension());

        $sizeLimit = (int)osc_max_size_kb()*1024;

        $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);

        // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
        $result = $uploader->handleUpload(osc_content_path().'/uploads/temp/');
        $result['uploadName'] = $uploader->getUploadName();

        // to pass data through iframe you will need to encode all html tags
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);

    }
    osc_add_hook('ajax_fu_upload', 'ajax_fu_upload');

    function fu_clear_session()
    {
        // clear session
        Session::newInstance()->_drop('fu_pre_images');
        Session::newInstance()->_dropKeepForm('fu_pre_images');
    }

    function fu_header_script()
    {
        // <div id="restricted-fine-uploader"></div>
        $aExt = explode(',',osc_allowed_extension());
        foreach($aExt as $key => $value) {
            $aExt[$key] = "'".$value."'";
        }

        $allowedExtensions = join(',', $aExt);
        $maxSize    = (int) osc_max_size_kb()*1024;
        $maxImages  = (int) osc_max_images_per_item();
        ?>

        <script>
        $(document).ready(function() {

            $('#restricted-fine-uploader').on('click','.primary_image', function(event){
                if(parseInt($("div.primary_image").index(this))>0){

                    var a_src   = $(this).parent().find('.fu_preview_img img').attr('src');
                    var a_title = $(this).parent().find('.fu_preview_img img').attr('alt');
                    var a_input = $(this).parent().find('input').attr('value');
                    // info
                    var a1 = $(this).parent().find('span.qq-upload-file').text();
                    var a2 = $(this).parent().find('span.qq-upload-size').text();

                    var li_first =  $('ul.qq-upload-list li').get(0);

                    var b_src   = $(li_first).find('.fu_preview_img img').attr('src');
                    var b_title = $(li_first).find('.fu_preview_img img').attr('alt');
                    var b_input = $(li_first).find('input').attr('value');
                    var b1      = $(li_first).find('span.qq-upload-file').text();
                    var b2      = $(li_first).find('span.qq-upload-size').text();

                    $(li_first).find('.fu_preview_img img').attr('src', a_src);
                    $(li_first).find('.fu_preview_img img').attr('alt', a_title);
                    $(li_first).find('input').attr('value', a_input);
                    $(li_first).find('span.qq-upload-file').text(a1);
                    $(li_first).find('span.qq-upload-size').text(a2);

                    $(this).parent().find('.fu_preview_img img').attr('src', b_src);
                    $(this).parent().find('.fu_preview_img img').attr('alt', b_title);
                    $(this).parent().find('input').attr('value', b_input);
                    $(this).parent().find('span.qq-upload-file').text(b1);
                    $(this).parent().find('span.qq-upload-file').text(b2);
                }
            });

            $('#restricted-fine-uploader').on('click','.primary_image', function(event){
                $(this).addClass('over primary');
            });

            $('#restricted-fine-uploader').on('mouseenter mouseleave','.primary_image', function(event){
                if(event.type=='mouseenter') {
                    if(!$(this).hasClass('primary')) {
                        $(this).addClass('primary');
                    }
                } else {
                    if(parseInt($("div.primary_image").index(this))>0){
                        $(this).removeClass('primary');
                    }
                }
            });


            $('#restricted-fine-uploader').on('mouseenter mouseleave','li.qq-upload-success', function(event){
                if(parseInt($("li.qq-upload-success").index(this))>0){

                    if(event.type=='mouseenter') {
                        $(this).find('div.primary_image').addClass('over');
                    } else {
                        $(this).find('div.primary_image').removeClass('over');
                    }
                }
            });

            window.removed_images = 0;
            $('#restricted-fine-uploader').on('click', 'a.qq-upload-delete', function(event) {
                window.removed_images = window.removed_images+1;
                $('#restricted-fine-uploader .alert-error').remove();
            });

            $('#restricted-fine-uploader').fineUploader({
                request: {
                    endpoint: '<?php echo osc_ajax_hook_url('fu_upload'); ?>'
                },
                multiple: true,
                validation: {
                    allowedExtensions: [<?php echo $allowedExtensions; ?>],
                    sizeLimit: <?php echo $maxSize; ?>,
                    itemLimit: <?php echo $maxImages; ?>
                },
                messages: {
                    tooManyItemsError: '<?php echo osc_esc_js(__('Too many items ({netItems}) would be uploaded. Item limit is {itemLimit}.', 'image_uploader'));?>',
                    onLeave: '<?php echo osc_esc_js(__('The files are being uploaded, if you leave now the upload will be cancelled.', 'image_uploader'));?>',
                    typeError: '<?php echo osc_esc_js(__('{file} has an invalid extension. Valid extension(s): {extensions}.', 'image_uploader'));?>',
                    sizeError: '<?php echo osc_esc_js(__('{file} is too large, maximum file size is {sizeLimit}.', 'image_uploader'));?>',
                    emptyError: '<?php echo osc_esc_js(__('{file} is empty, please select files again without it.', 'image_uploader'));?>'
                },
                deleteFile: {
                    enabled: true,
                    method: "POST",
                    forceConfirm: false,
                    endpoint: '<?php echo osc_ajax_plugin_url(osc_plugin_folder(__FILE__).'server/success.php'); ?>'
                },
                retry: {
                    showAutoRetryNote : true,
                    showButton: true
                },
                text: {
                    uploadButton: '<?php _e('Click or Drop for upload images','image_uploader'); ?>'
                },
                showMessage: function(message) {
                    // Using Bootstrap's classes
                    $('#restricted-fine-uploader').append('<div class="alert alert-error">' + message + '</div>');
                    }
                }).on('statusChange', function(event, id, old_status, new_status) {
                    $(".alert.alert-error").remove();
                }).on('complete', function(event, id, fileName, responseJSON) {
                    if (responseJSON.success) {
                        var new_id = id - removed_images;
                        var li = $('.qq-upload-list li')[new_id];
                        <?php if(Params::getParam('action')=='item_add') { ?>
                        if(parseInt(new_id)==0) {
                            $(li).append('<div class="primary_image primary"></div>');
                        } else {
                            $(li).append('<div class="primary_image"><a title="<?php echo osc_esc_html(__('Make primary image', 'image_uploader')); ?>"></a></div>');
                        }
                        <?php } ?>
                        $(li).append('<div class="fu_preview_img"><img src="<?php echo osc_base_url(); ?>oc-content/uploads/temp/'+fileName+'" alt="' + responseJSON.uploadName + '"></div>');
                        $(li).append('<input type="hidden" name="fu_images[]" value="'+responseJSON.uploadName+'"></input>');
                    }
                <?php if(Params::getParam('action')=='item_edit') { ?>
                }).on('validateBatch', function(event, fileOrBlobDataArray) {
                    // clear alert messages
                    if($('#restricted-fine-uploader .alert-error').size()>0) {
                        $('#restricted-fine-uploader .alert-error').remove();
                    }

                    var len = fileOrBlobDataArray.length;
                    var result = canContinue(len);
                    return result.success;

                });

                function canContinue(numUpload) {
                    // strUrl is whatever URL you need to call
                    var strUrl      = "<?php echo osc_ajax_hook_url('fu_validate'); ?>&id=<?php echo osc_item_id(); ?>&secret=<?php echo osc_item_secret(); ?>";
                    var strReturn   = {};

                    jQuery.ajax({
                        url: strUrl,
                        success: function(html) {
                        strReturn = html;
                        },
                        async:false
                    });
                    var json  = JSON.parse(strReturn);
                    var total = parseInt(json.count) + $("#restricted-fine-uploader input[name='fu_images[]']").size() + (numUpload);
                    if(total<=<?php echo $maxImages;?>) {
                        json.success = true;
                    } else {
                        json.success = false;

                        $('#restricted-fine-uploader .qq-uploader').after($('<div class="alert alert-error"><?php _e('Too many items');?> ('+total+') <?php echo sprintf(__('would be uploaded. Item limit is %d.', 'image_uploader'), $maxImages); ?></div>'));
                    }
                    return json;
                }

                <?php } else { ?>
                });
                <?php } ?>
        });
        </script>
        <?php
    }




    function fu_add_image_resources($item)
    {
        $wat = new Watermark();
        $itemId = $item['pk_i_id'];

        $itemResourceManager = ItemResource::newInstance();
        $aImages = Params::getParam('fu_images');
        if (is_array($aImages)) {
            foreach($aImages as $img) {

                $itemResourceManager->insert(array(
                    'fk_i_item_id' => $itemId
                ));
                $resourceId = $itemResourceManager->dao->insertedId();


                $tmpName = osc_content_path().'/uploads/temp/'.$img;
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



                osc_copy($tmpName.'_normal', osc_content_path() .'/uploads/' . $resourceId . '.jpg');
                osc_copy($tmpName.'_preview', osc_content_path() .'/uploads/' . $resourceId . '_preview.jpg');
                osc_copy($tmpName.'_thumbnail', osc_content_path() .'/uploads/' . $resourceId . '_thumbnail.jpg');
                if( osc_keep_original_image() ) {
                    $path = osc_content_path() .'/uploads/' . $resourceId.'_original.jpg';
                    move_uploaded_file($tmpName, $path);
                }

                $s_path = 'oc-content/uploads/';
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

        if(! ($page=="item" && ($action=="item_add_post" || $action=="item_edit_post") ) ) {
            // clear session
            osc_add_hook("footer", 'fu_clear_session', 9);
        }
        if($page=="item" && ($action=="item_add" || $action=="item_edit") ) {
            osc_add_hook('footer','fu_header_script');

            // js and css
            osc_enqueue_style('fu-fine-uploader-css', osc_base_url() . 'oc-content/plugins/image_uploader/jquery.fineuploader/fineuploader.css');
            osc_enqueue_style('fu-fine-uploader-custom-css', osc_base_url() . 'oc-content/plugins/image_uploader/css/custom.css');

            osc_register_script('fu-fine-uploader-js', osc_base_url() . 'oc-content/plugins/image_uploader/jquery.fineuploader/jquery.fineuploader-3.8.0.min.js', 'jquery');
            osc_enqueue_script('fu-fine-uploader-js');
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
        $pathname = osc_content_path() .'/uploads/temp';
        @mkdir($pathname);
    }

    function fu_uninstall()
    {
        // remove temp directory
        $dirname = osc_content_path() .'/uploads/temp';
        @rmdir($dirname);
    }

    osc_register_plugin(osc_plugin_path(__FILE__), 'fu_install' );
    osc_add_hook(osc_plugin_path(__FILE__)."_uninstall", 'fu_uninstall' );

    /**
     * Remove files > 1h
     */
    function fu_cron_hourly() {

        $dir = osc_content_path() .'/uploads/temp';
        if ($manager = opendir($dir)) {
            while (false !== ($entrada = readdir($manager))) {
                if ($entrada != "." && $entrada != "..") {
                    $filename = $dir .'/'. $entrada;
                    $now = new DateTime();
                    $d = new DateTime( );
                    $d->setTimestamp(filectime($filename));
                    $diff = $d->diff($now);

                    $min = $diff->i;
                    $min = $min + ($diff->h * 60);
                    $min = $min + ($diff->d * 24 * 60);

                    // more than 60 min remove temp image
                    if ($min >= 60) {
                        @unlink($filename);
                    }
                }
            }
            closedir($manager);
        }
    }
    osc_add_hook('cron_hourly', 'fu_cron_hourly');
    osc_add_hook('init', 'fu_cron_hourly');
?>