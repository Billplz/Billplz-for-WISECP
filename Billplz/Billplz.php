<?php

include 'classes/BillplzClient.php';
include 'classes/BillplzAPI.php';

class Billplz extends PaymentGatewayModule
{
    public $gatewayId;

    private $api;

    private $apiKey;
    private $xsignatureKey;
    private $collectionId;
    private $sandbox;

    public function __construct()
    {
        $this->gatewayId = __CLASS__;

        $this->config = Modules::Config('Payment', $this->gatewayId);
        $this->lang   = Modules::Lang('Payment', $this->gatewayId);

        $this->name   = $this->config['meta']['name'];

        $this->initSettings();
        $this->initApi();

        parent::__construct();
    }

    private function initSettings()
    {
        $this->apiKey        = $this->config['settings']['api_key'] ?: '';
        $this->xsignatureKey = $this->config['settings']['xsignature_key'] ?: '';
        $this->collectionId  = $this->config['settings']['collection_id'] ?: '';
        $this->sandbox       = (bool) $this->config['settings']['sandbox'] ?: false;
    }

    private function initApi()
    {
        $this->api = new BillplzAPI();;
        $this->api->setApiKey($this->apiKey, $this->sandbox);
        $this->api->setXsignatureKey($this->xsignatureKey);
    }

    public function area()
    {
        try {
            if (!$this->apiKey) {
                throw new Exception($this->lang['error']['missing-api-key']);
            }

            if (!$this->collectionId) {
                throw new Exception($this->lang['error']['missing-collection-id']);
            }

            $checkoutData = $this->getCheckoutData($this->checkout);

            $itemNames = array_column($checkoutData['items'], 'name');
            $description = implode( ', ', $itemNames);

            $callbackUrlQuery = http_build_query(array(
                'checkout_id'       => $this->checkout_id,
                'checkout_checksum' => $checkoutData['checksum'],
            ));

            $callbackUrl = $this->links['callback'] . '?' . $callbackUrlQuery;
            $redirectUrl = $callbackUrl . '&return=true';

            $params = array(
                'collection_id'     => $this->collectionId,
                'description'       => $description,
                'name'              => $checkoutData['user']['name'],
                'email'             => $checkoutData['user']['email'],
                'mobile'            => $checkoutData['user']['phone'],
                'amount'            => (int) $checkoutData['total'],
                'callback_url'      => $callbackUrl,
                'redirect_url'      => $redirectUrl,
                'reference_1_label' => $this->lang['checkout-id'],
                'reference_1'       => '#'.$this->checkout_id,
            );

            list($code, $response) = $this->api->createBill($params);

            Modules::save_log('Payment', $this->name, 'link', $params, $response);

            switch ($code) {
                case 401:
                    throw new Exception($this->lang['error']['invalid-api-key']);
                    break;

                case 404:
                    throw new Exception($this->lang['error']['invalid-collection-id']);
                    break;
            }

            $billUrl = isset($response['url']) ? $response['url'] : false;

            header('Location: ' . $response['url']);

            return $this->lang['redirect-message'];
        } catch (Exception $e) {
            return str_replace(':error', $e->getMessage(), $this->lang['error']['payment-error']);
        }
    }

    private function getCheckoutData($checkout)
    {
        $checkoutId    = isset($checkout['id']) ? (int) $checkout['id'] : false;
        $checkoutData  = isset($checkout['data']) ? (array) $checkout['data'] : false;
        $checkoutItems = isset($checkout['items']) ? (array) $checkout['items'] : false;
        $userData      = isset($checkoutData['user_data']) ? (array) $checkoutData['user_data'] : false;

        if (!$checkoutData) {
            throw new Exception($this->lang['error']['missing-checkout-data']);
        }

        if (!$checkoutItems) {
            throw new Exception($this->lang['error']['missing-checkout-items']);
        }

        if (!$userData) {
            throw new Exception($this->lang['error']['missing-user-data']);
        }

        $userName = isset($userData['full_name']) ? $userData['full_name'] : false;
        $userEmail = isset($userData['email']) ? $userData['email'] : false;
        $userPhone = isset($userData['phone']) ? $userData['phone'] : false;

        if (!$userName) {
            throw new Exception($this->lang['error']['missing-user-name']);
        }

        if (!$userEmail) {
            throw new Exception($this->lang['error']['missing-user-email']);
        }

        if (!$userPhone) {
            throw new Exception($this->lang['error']['missing-user-phone']);
        }

        $userPhone = preg_replace('/[^0-9]/', '', $userPhone);

        $result = array(
            'id'    => $checkoutId,
            'items' => $checkoutItems,
            'total' => (int) round($checkoutData['total'] * 100),
            'user'  => array(
                'name'  => $userName,
                'email' => $userEmail,
                'phone' => $userPhone,
            ),
        );

        $result['checksum'] = $this->generateCheckoutChecksum(array(
            'id'    => $result['id'],
            'total' => $result['total'],
            'email' => $result['user']['email'],
        ));

        return $result;
    }

    public function callback()
    {
        try {
            if (Filter::init('GET/return')) {
                return $this->thankyou();
            }

            if (!Filter::isPOST()) {
                throw new Exception($this->lang['error']['invalid-request']);
            }

            $checkoutId = isset($_GET['checkout_id']) ? $_GET['checkout_id'] : false;
            $checkoutChecksum = isset($_GET['checkout_checksum']) ? $_GET['checkout_checksum'] : false;

            if (!$checkoutId) {
                throw new Exception($this->lang['error']['missing-checkout-id']);
            }

            if (!$checkoutChecksum) {
                throw new Exception($this->lang['error']['missing-checkout-checksum']);
            }

            $checkout = $this->get_checkout($checkoutId);

            if (!$checkout) {
                throw new Exception($this->lang['error']['checkout-not-found']);
            }

            if (!$this->apiKey) {
                throw new Exception($this->lang['error']['missing-api-key']);
            }

            if (!$this->xsignatureKey) {
                throw new Exception($this->lang['error']['missing-xsignature-key']);
            }

            $checkoutData = $this->getCheckoutData($checkout);

            $response = $this->api->getIpnResponse();

            if ($this->api->validateIpnResponse($response)) {
                // Checkout data from callback request
                $checkoutChecksumData = array(
                    'id'    => (int) $checkoutId,
                    'total' => (int) $response['amount'],
                    'email' => $response['email'],
                );

                if (!$this->isValidCheckoutChecksum($checkoutChecksum, $checkoutChecksumData)) {
                    throw new Exception($this->lang['error']['invalid-checkout-checksum']);
                }

                $paymentData[ $this->lang['bill-id'] ] = $response['id'];

                if (isset($response['transaction_id'])) {
                    $paymentData[ $this->lang['transaction-id'] ] = $response['transaction_id'];
                }

                $paymentData[ $this->lang['sandbox'] ] = ($this->sandbox == true) ? $this->lang['yes'] : $this->lang['no'];

                $isPaymentSuccess = false;

                if (isset($response['transaction_status'])) {
                    switch ($response['transaction_status']) {
                        case 'completed':
                            $isPaymentSuccess = true;
                            $paymentMessage = $this->lang['payment-success'];
                            break;

                        case 'failed':
                            $paymentMessage = $this->lang['payment-failed'];
                            break;

                        default:
                            $paymentMessage = $this->lang['payment-pending'];
                            break;
                    }
                } elseif ($response['paid'] == true) {
                    $isPaymentSuccess = true;
                    $paymentMessage = $this->lang['payment-success'];
                } else {
                    $paymentMessage = $this->lang['payment-pending'];
                }

                $paymentMessage = str_replace(':checkout_id', $paymentMessage, $checkoutId);

                $paymentStatus = ($isPaymentSuccess == true) ? 'successful' : 'error';

                $abc = array(
                    'status'           => $paymentStatus,
                    'message'          => $paymentData,
                    'callback_message' => json_encode(array(
                        'success' => true,
                        'message' => $paymentMessage,
                    )),
                );

                return array(
                    'status'           => $paymentStatus,
                    'message'          => $paymentData,
                    'callback_message' => json_encode(array(
                        'success' => true,
                        'message' => $paymentMessage,
                    )),
                );
            }
        } catch (Exception $e) {
            $errorMessage = str_replace(':error', $e->getMessage(), $this->lang['error']['callback-error']);

            return array(
                'status'           => 'error',
                'message'          => $errorMessage,
                'callback_message' => json_encode(array(
                    'success' => false,
                    'message' => $errorMessage,
                )),
            );
        }
    }

    public function thankyou()
    {
        try {
            if (!Filter::isGET()) {
                throw new Exception($this->lang['error']['invalid-request']);
            }

            $checkoutId = isset($_GET['checkout_id']) ? $_GET['checkout_id'] : false;

            if (!$checkoutId) {
                throw new Exception($this->lang['error']['missing-checkout-id']);
            }

            $checkout = $this->get_checkout($checkoutId);

            if (!$checkout) {
                throw new Exception($this->lang['error']['checkout-not-found']);
            }

            if (!$this->apiKey) {
                throw new Exception($this->lang['error']['missing-api-key']);
            }

            if (!$this->xsignatureKey) {
                throw new Exception($this->lang['error']['missing-xsignature-key']);
            }

            $response = $this->api->getIpnResponse();

            if ($this->api->validateIpnResponse($response)) {
                if (isset($response['billplztransaction_status'])) {
                    switch ($response['billplztransaction_status']) {
                        case 'completed':
                            $paymentStatus = 'success';
                            break;

                        case 'failed':
                            $paymentStatus = 'failed';
                            break;

                        default:
                            $paymentStatus = 'pending';
                            break;
                    }
                } elseif ($response['billplzpaid'] == true) {
                    $paymentStatus = 'success';
                } else {
                    $paymentStatus = 'pending';
                }

                switch ($paymentStatus) {
                    case 'success':
                        $redirectTo = $this->links['successful'];
                        break;

                    case 'failed':
                        $redirectTo = $this->links['failed'];
                        break;

                    default:
                        $redirectTo = false;
                        break;
                }

                if ($redirectTo) {
                    echo $this->lang['redirect-thankyou'];
                    header('Location: ' . $redirectTo);
                    exit;
                }

                echo $this->lang['thankyou-payment-pending'];
                header('Refresh: 5; URL=' . $redirectTo);
                exit;
            }
        } catch (Exception $e) {
            echo $this->lang['error']['invalid-request'];
            exit;
        }
    }

    // Generate a hashed string for checkout data to ensure that the callback can be verified as valid for the specified checkout ID
    private function generateCheckoutChecksum(array $data)
    {
        if (!$this->xsignatureKey) {
            throw new Exception($this->lang['error']['missing-xsignature-key']);
        }

        foreach ($data as $key => $value) {
            $data[] = $key . $value;
        }

        $data = implode('|', $data);

        return hash_hmac('sha256', $data, $this->xsignatureKey);
    }

    // Verify the checkout data checksum
    private function isValidCheckoutChecksum($checksum, array $data)
    {
        $generatedChecksum = $this->generateCheckoutChecksum($data);

        return $checksum === $generatedChecksum;
    }
}
