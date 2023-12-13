<?php
abstract class BillplzClient
{
    const PRODUCTION_URL = 'https://www.billplz.com/api/';
    const SANDBOX_URL    = 'https://www.billplz-sandbox.com/api/';

    private $url;
    private $headers = array();

    private $apiKey;
    private $xsignatureKey;

    private $sandbox = true;
    protected $debug = false;

    // Set API key
    public function setApiKey($apiKey, $sandbox = true)
    {
        $this->apiKey = $apiKey;
        $this->useSandbox($sandbox);

        $this->headers['Authorization'] = 'Basic ' . base64_encode($this->apiKey . ':');

    }

    // Set X-Signature key
    public function setXsignatureKey($xsignatureKey)
    {
        $this->xsignatureKey = $xsignatureKey;
    }

    // Set sandbox
    public function useSandbox($sandbox = true)
    {
        $this->sandbox = (bool) $sandbox;

        if ($this->sandbox == true) {
            $this->url = self::SANDBOX_URL;
        } else {
            $this->url = self::PRODUCTION_URL;
        }

    }

    // Set debug
    public function setDebug($debug = true)
    {
        $this->debug = (bool) $debug;
    }

    // HTTP request URL
    private function getUrl($route = null)
    {
        return $this->url . $route;
    }

    // HTTP request headers
    private function getHeaders()
    {
        $this->headers['Accept'] = 'application/json';

        return $this->headers;

    }

    // HTTP GET request
    protected function get($route, $params = array())
    {
        return $this->request($route, $params, 'GET');
    }

    // HTTP POST request
    protected function post($route, $params = array())
    {
        return $this->request($route, $params);
    }

    // HTTP request
    protected function request($route, $params = array(), $method = 'POST')
    {
        if (!$this->apiKey) {
            throw new Exception('Missing API key');
        }

        $url = $this->getUrl($route);
        $headers = $this->getHeaders();

        $this->log('URL: ' . $url);
        $this->log("Headers: \n" . json_encode($headers, JSON_PRETTY_PRINT));
        $this->log("Body: \n" . json_encode($params, JSON_PRETTY_PRINT));

        $httpHeaders = array();

        foreach ( $headers as $key => $value) {
            $httpHeaders[] = "{$key}: {$value}";
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $httpHeaders,
        ));

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
        }

        if ($params) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
        }

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $body = json_decode($response, true);

        curl_close($curl);

        $this->log("Response: \n" . json_encode($body, JSON_PRETTY_PRINT));

        return array($code, $body);
    }

    // Generate checksum string for API v5
    protected function generateApiV5Checksum(array $data, array $params, array $optionalParams = array())
    {
        if ( !$this->xsignatureKey ) {
            throw new Exception( 'Missing X-Signature key' );
        }

        $checksumData = array();

        foreach ($params as $param) {
            $value = isset($data[$param]) ? $data[$param] : false;

            if (!in_array($param, $optionalParams) && !$value) {
                throw new Exception("Missing required parameter for checksum validation: {$param}");
            }

            $checksumData[] = $data[$param];
        }

        return hash_hmac('sha512', implode('', $checksumData), $this->xsignatureKey);

    }

    // Get a list of SWIFT banks
    public function getSwiftBanks()
    {
        $swiftBanks = array(
            'PHBMMYKL' => 'Affin Bank Berhad',
            'AGOBMYKL' => 'AGROBANK / BANK PERTANIAN MALAYSIA BERHAD',
            'MFBBMYKL' => 'Alliance Bank Malaysia Berhad',
            'RJHIMYKL' => 'AL RAJHI BANKING &amp; INVESTMENT CORPORATION (MALAYSIA) BERHAD',
            'ARBKMYKL' => 'AmBank (M) Berhad',
            'BIMBMYKL' => 'Bank Islam Malaysia Berhad',
            'BKRMMYKL' => 'Bank Kerjasama Rakyat Malaysia Berhad',
            'BMMBMYKL' => 'Bank Muamalat (Malaysia) Berhad',
            'BSNAMYK1' => 'Bank Simpanan Nasional Berhad',
            'CIBBMYKL' => 'CIMB Bank Berhad',
            'CITIMYKL' => 'Citibank Berhad',
            'HLBBMYKL' => 'Hong Leong Bank Berhad',
            'HBMBMYKL' => 'HSBC Bank Malaysia Berhad',
            'KFHOMYKL' => 'Kuwait Finance House',
            'MBBEMYKL' => 'Maybank / Malayan Banking Berhad',
            'OCBCMYKL' => 'OCBC Bank (Malaysia) Berhad',
            'PBBEMYKL' => 'Public Bank Berhad',
            'RHBBMYKL' => 'RHB Bank Berhad',
            'SCBLMYKX' => 'Standard Chartered Bank (Malaysia) Berhad',
            'UOVBMYKL' => 'United Overseas Bank (Malaysia) Berhad',
        );

        if ($this->sandbox == true) {
            $swiftBanks = array_merge(array(
                'DUMMYBANKVERIFIED' => 'Billplz Dummy Bank Verified',
           ), $swiftBanks);
        }

        return $swiftBanks;

    }

    // Get IPN response
    public function getIpnResponse()
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], array('GET', 'POST'))) {
            throw new Exception('Invalid IPN response');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $response = $this->getValidIpnCallbackResponse();
        } else {
            $response = $this->getValidIpnRedirectResponse();
        }

        if (!$response) {
            throw new Exception('Invalid IPN response');
        }

        return $response;

    }

    // Get IPN (callback) response
    private function getValidIpnCallbackResponse()
    {
        $requiredParams = $this->getIpnCallbackParams();
        $optionalParams = $this->getIpnOptionalParams();

        $params = array_merge($requiredParams, $optionalParams);

        $allowedParams = array();

        foreach ($params as $param) {
            // Skip if optional parameters are not passed in the URL
            if (in_array($param, $optionalParams) && !isset($_POST[$param])) {
                continue;
            }

            if (!isset($_POST[$param])) {
                throw new Exception(sprintf('Missing IPN parameter - %s', $param));
            }

            $allowedParams[$param] = trim($_POST[$param]);
        }

        // Returns only the allowed response data
        return $allowedParams;

    }

    // Get IPN (redirect) response
    private function getValidIpnRedirectResponse()
    {
        $requiredParams = $this->getIpnRedirectParams();
        $optionalParams = $this->getIpnOptionalParams();

        $params = array_merge($requiredParams, $optionalParams);

        $allowedParams = array();

        foreach ($params as $param) {
            // Skip if optional parameters are not passed in the URL
            if (in_array($param, $optionalParams) && !isset($_GET['billplz'][$param])) {
                continue;
            }

            if (!isset($_GET['billplz'][$param])) {
                throw new Exception(sprintf('Missing IPN parameter - %s', $param));
            }

            $newParamKey = $param;

            if ($param != 'x_signature') {
                $newParamKey = 'billplz' . $param;
            }

            $allowedParams[$newParamKey] = trim($_GET['billplz'][$param]);
        }

        // Returns only the allowed response data
        return $allowedParams;

    }

    // Required parameters for IPN (callback) response
    private function getIpnCallbackParams()
    {
        return array(
            'amount',
            'collection_id',
            'due_at',
            'email',
            'id',
            'mobile',
            'name',
            'paid_amount',
            'paid_at',
            'paid',
            'state',
            'transaction_id',
            'transaction_status',
            'url',
            'x_signature',
        );

    }

    // Required parameters for IPN (redirect) response
    private function getIpnRedirectParams()
    {
        return array(
            'id',
            'paid_at',
            'paid',
            'transaction_id',
            'transaction_status',
            'x_signature',
        );

    }

    // Optional parameters for IPN response (both callback and redirect) if Extra Payment Completion Information is enabled
    private function getIpnOptionalParams()
    {
        return array(
            'transaction_id',
            'transaction_status',
        );

    }

    // Validate the IPN response
    public function validateIpnResponse($response)
    {
        if (!$this->verifySignature($response)) {
            throw new Exception('Signature mismatch');
        }

        return true;

    }

    // Verify the signature value in the IPN response
    private function verifySignature($response)
    {
        if ( !$this->xsignatureKey ) {
            throw new Exception( 'Missing X-Signature key' );
        }

        $ipnSignature = isset($response['x_signature']) ? $response['x_signature'] : null;

        if (!$ipnSignature) {
            throw new Exception('Missing IPN signature');
        }

        unset($response['x_signature']);

        $data = array();

        foreach ($response as $key => $value) {
            $data[] = $key . $value;
        }

        // Generate a signature using the response data and X-Signature from Billplz dashboard
        $encodedData = implode('|', $data);
        $generatedSignature = hash_hmac('sha256', $encodedData, $this->xsignatureKey);

        // Compare the generated signature value with the signature value in the IPN response
        return $ipnSignature === $generatedSignature;

    }

    // Get the payment order callback response
    public function getPaymentOrderCallbackResponse()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Invalid payment order callback response');
        }

        $params = $this->getPaymentOrderCallbackParams();
        $response = array();

        foreach ($params as $param) {
            if (!isset($_POST[$param])) {
                throw new Exception(sprintf('Missing payment order callback parameter - %s', $param));
            }

            $response[$param] = trim($_POST[$param]);
        }

        if (!$response) {
            throw new Exception('Invalid payment order callback response');
        }

        return $response;

    }

    // Required parameters for payment order callback response
    private function getPaymentOrderCallbackParams()
    {
        return array(
            'id',
            'payment_order_collection_id',
            'bank_code',
            'bank_account_number',
            'name',
            'description',
            'email',
            'status',
            'notification',
            'recipient_notification',
            'reference_id',
            'display_name',
            'total',
            'epoch',
            'checksum',
        );

    }

    // Validate the payment order callback response
    public function validatePaymentOrderCallbackResponse($response)
    {
        $generatedChecksum = $this->generateApiV5Checksum(
            $response,
            array('id', 'bank_account_number', 'status', 'total', 'reference_id', 'epoch'),
        );

        if ($response['checksum'] !== $generatedChecksum) {
            throw new Exception('Signature mismatch');
        }

        return true;

    }

    // Debug logging
    protected function log($mesage)
    {
    }
}
