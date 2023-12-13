<?php
if (!defined('CORE_FOLDER')) exit;

$config = $module->config;
$lang = $module->lang;

try {
    $new_settings = array(
        'api_key'              => Filter::init('POST/api_key', 'hclear'),
        'xsignature_key'       => Filter::init('POST/xsignature_key', 'hclear'),
        'collection_id'        => Filter::init('POST/collection_id', 'hclear'),
        'sandbox'              => (bool) (int) Filter::init('POST/sandbox', 'numbers'),

        'commission_rate'      => Filter::init('POST/commission_rate', 'amount'),
    );

    $new_settings['commission_rate'] = str_replace(',', '.', $new_settings['commission_rate']);

    if (!$new_settings['api_key']) {
        throw new Exception(
            str_replace(
                ':attribute',
                $lang['settings']['api-key']['label'],
                $lang['error']['missing-api-key']
            )
        );
    }

    if (!$new_settings['xsignature_key']) {
        throw new Exception(
            str_replace(
                ':attribute',
                $lang['settings']['xsignature-key']['label'],
                $lang['error']['missing-xsignature-key']
            )
        );
    }

    if (!$new_settings['collection_id']) {
        throw new Exception(
            str_replace(
                ':attribute',
                $lang['settings']['collection-id']['label'],
                $lang['error']['missing-collection-id']
            )
        );
    }

    $billplz = new BillplzAPI();
    $billplz->setApiKey($new_settings['api_key'], $new_settings['sandbox']);

    list($code, $response) = $billplz->getCollection($new_settings['collection_id']);

    switch ($code) {
        case 401:
            throw new Exception($lang['error']['invalid-api-key']);
            break;

        case 404:
            throw new Exception($lang['error']['invalid-collection-id']);
            break;
    }

    // Update the settings value to the new value
    foreach ($new_settings as $key => $value) {
        $config['settings'][$key] = $value;
    }

    $array_export = Utility::array_export($config, array('pwith' => true));

    // Save the updated settings into the config.php file
    $file_name = dirname(__DIR__) . DS . 'config.php';
    $file_write = FileManager::file_write($file_name, $array_export);

    $user_data = UserManager::LoginData('admin');

    User::addAction($user_data['id'], 'alteration', 'changed-payment-module-settings', array(
        'module' => $config['meta']['name'],
        'name' => $lang['name'],
    ));

    echo Utility::jencode(array(
        'status' => 'successful',
        'message' => $lang['settings-success'],
    ));
} catch (Exception $e) {
    echo Utility::jencode(array(
        'status' => 'error',
        'message' => $e->getMessage(),
    ));
}
