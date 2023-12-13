<?php
class BillplzAPI extends BillplzClient
{
    // Webhook rank
    public function getWebhookRank()
    {
        return $this->get('v4/webhook_rank');
    }

    // Create a bill
    public function createBill(array $params)
    {
        return $this->post('v3/bills', $params);
    }

    // Get a bill
    public function getBill($billId)
    {
        return $this->get("v3/bills/{$billId}");
    }

    // Delete a bill
    public function deleteBill($billId)
    {
        return $this->delete("v3/bills/{$billId}");
    }

    // Create a collection
    public function createCollection(array $params)
    {
        return $this->post('v4/collections', $params);
    }

    // Get a collection
    public function getCollection($collectionId)
    {
        return $this->get("v4/collections/{$collectionId}");
    }

    // Get collection index
    public function getCollectionIndex(array $params = array())
    {
        return $this->get('v4/collections', $params);
    }

    // Create an open collection
    public function createOpenCollection(array $params)
    {
        return $this->post('v4/open_collections', $params);
    }

    // Get an open collection
    public function getOpenCollection($collectionId)
    {
        return $this->get("v4/open_collections/{$collectionId}");
    }

    // Get open collection index
    public function getOpenCollectionIndex(array $params = array())
    {
        return $this->get('v4/open_collections', $params);
    }

    // Activate customer receipt notification for specified collection
    public function activateCustomerReceiptDelivery($collectionId)
    {
        return $this->post("v4/collections/{$collectionId}/customer_receipt_delivery/activate");
    }

    // Deactivate customer receipt notification for specified collection
    public function deactivateCustomerReceiptDelivery($collectionId)
    {
        return $this->post("v4/collections/{$collectionId}/customer_receipt_delivery/deactivate");
    }

    // Set specified collection to follow Global Customer Receipt Notification configuration
    public function setGlobalCustomerReceiptDelivery($collectionId)
    {
        return $this->post("v4/collections/{$collectionId}/customer_receipt_delivery/global");
    }

    // Get a collection's customer receipt notification status
    public function getStatusCustomerReceiptDelivery($collectionId)
    {
        return $this->get("v4/collections/{$collectionId}/customer_receipt_delivery");
    }

    // Get payment gateways
    public function getPaymentGateways()
    {
        return $this->get('v4/payment_gateways');
    }

    // Create a payment order collection
    public function createPaymentOrderCollection(array $params)
    {
        $params['epoch'] = time();

        $params['checksum'] = $this->generateApiV5Checksum(
            $params,
            array('title', 'callback_url', 'epoch'),
            array('callback_url')
        );

        return $this->post('v5/payment_order_collections', $params);

    }

    // Get a payment order collection
    public function getPaymentOrderCollection($paymentOrderCollectionId, array $params)
    {
        $params['epoch'] = time();

        $params['checksum'] = $this->generateApiV5Checksum($params, array(
            'payment_order_collection_id',
            'epoch',
        ));

        return $this->get("v5/payment_order_collections/{$paymentOrderCollectionId}", $params);

    }

    // Create a payment order
    public function createPaymentOrder(array $params)
    {
        $params['epoch'] = time();

        $params['checksum'] = $this->generateApiV5Checksum($params, array(
            'payment_order_collection_id',
            'bank_account_number',
            'total',
            'epoch',
        ));

        return $this->post('v5/payment_orders', $params);

    }

    // Get a payment order
    public function getPaymentOrder($paymentOrderId, array $params)
    {
        $params['epoch'] = time();

        $params['checksum'] = $this->generateApiV5Checksum($params, array('payment_order_id', 'epoch'));

        return $this->get("v5/payment_orders/{$paymentOrderId}", $params);

    }

    // Get a payment order
    public function getPaymentOrderLimit()
    {
        $params['epoch'] = time();

        $params['checksum'] = $this->generateApiV5Checksum($params, array('epoch'));

        return $this->get('v5/payment_order_limit', $params);

    }
}
