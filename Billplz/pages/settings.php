<?php if (!defined('CORE_FOLDER')) exit; ?>

<form id="billplz-settings-form" method="POST" action="<?php echo Controllers::$init->getData('links')['controller']; ?>">
    <input type="hidden" name="operation" value="module_controller">
    <input type="hidden" name="module" value="Billplz">
    <input type="hidden" name="controller" value="settings">

    <div class="formcon">
        <label for="billplz-api-key" class="yuzde30" style="cursor: pointer;"><?php echo $module->lang['settings']['api-key']['label']; ?></label>
        <div class="yuzde70">
            <input id="billplz-api-key" type="text" name="api_key" value="<?php echo $module->config['settings']['api_key']; ?>">
            <span class="kinfo"><?php echo $module->lang['settings']['api-key']['description']; ?></span>
        </div>
    </div>

    <div class="formcon">
        <label for="billplz-xsignature-key" class="yuzde30" style="cursor: pointer;"><?php echo $module->lang['settings']['xsignature-key']['label']; ?></label>
        <div class="yuzde70">
            <input id="billplz-xsignature-key" type="text" name="xsignature_key" value="<?php echo $module->config['settings']['xsignature_key']; ?>">
            <span class="kinfo"><?php echo $module->lang['settings']['xsignature-key']['description']; ?></span>
        </div>
    </div>

    <div class="formcon">
        <label for="billplz-collection-id" class="yuzde30" style="cursor: pointer;"><?php echo $module->lang['settings']['collection-id']['label']; ?></label>
        <div class="yuzde70">
            <input id="billplz-collection-id" type="text" name="collection_id" value="<?php echo $module->config['settings']['collection_id']; ?>">
            <span class="kinfo"><?php echo $module->lang['settings']['collection-id']['description']; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $module->lang['settings']['sandbox']['label']; ?></div>
        <div class="yuzde70">
            <input id="billplz-sandbox" type="checkbox" name="sandbox" class="checkbox-custom" value="1" <?php echo ($module->config['settings']['sandbox'] ? 'checked' : ''); ?>>
            <label for="billplz-sandbox" class="checkbox-custom-label">
                <span class="kinfo"><?php echo str_replace(':sandbox_url', 'https://billplz-sandbox.com/', $module->lang['settings']['sandbox']['description']); ?></span>
            </label>
        </div>
    </div>

    <div class="formcon">
        <label for="billplz-commission-rate" class="yuzde30" style="cursor: pointer;"><?php echo $module->lang['settings']['commission-rate']['label']; ?></label>
        <div class="yuzde70">
            <input id="billplz-commission-rate" type="text" name="commission_rate" value="<?php echo $module->config['settings']['commission_rate']; ?>" style="width: 80px;">
            <span class="kinfo"><?php echo $module->lang['settings']['commission-rate']['description']; ?></span>
        </div>
    </div>

    <div class="guncellebtn yuzde30" style="float: right;">
        <button id="billplz-settings-form-submit" type="submit" class="yesilbtn gonderbtn"><?php echo $module->lang['save-settings']; ?></button>
    </div>
</form>

<script type="text/javascript">
    $(document).ready(function() {
        $('#billplz-settings-form-submit').on('click', function(e) {
            e.preventDefault();

            MioAjaxElement($(this), {
                waiting_text: waiting_text,
                progress_text: progress_text,
                result: 'billplzSettingsFormHandler',
            });
        });
    });

    function billplzSettingsFormHandler(result) {
        if (result != '') {
            var solve = getJson(result);

            if (solve !== false) {
                if (solve.status === 'error') {
                    if (solve.for != undefined && solve.for != '') {
                        $('#billplz-settings-form ' + solve.for).focus();
                        $('#billplz-settings-form ' + solve.for).attr('style', 'color: red; border-bottom: 2px solid red;');
                        $('#billplz-settings-form ' + solve.for).change(function() {
                            $(this).removeAttr('style');
                        });
                    }

                    if (solve.message != undefined && solve.message != '') {
                        alert_error(solve.message, {timer: 5000 });
                    }
                } else if (solve.status === 'successful') {
                    alert_success(solve.message, {timer: 2500});
                } else {
                    console.log(result);
                }
            }
        }
    }
</script>
