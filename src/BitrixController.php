<?php

require_once __DIR__ . '/../crest/crest.php';

class BitrixController
{
    public function addLead(int $entityTypeId, array $leadData): ?array
    {
        if (empty($leadData['TITLE'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Missing required lead field: TITLE']);
            exit;
        }

        $result = CRest::call('crm.item.add', [
            'entityTypeId' => $entityTypeId,
            'fields' => $leadData,
        ]);

        if (isset($result['result']['item'])) {
            return $result['result']['item'];
        }

        return null;
    }

    public function getDeal(int $dealId): ?array
    {
        $result = CRest::call("crm.deal.get", [
            'ID' => $dealId
        ]);

        if (isset($result['result'])) {
            return $result['result'];
        }

        return null;
    }

    public function getContact(int $contactId): ?array
    {
        $result = CRest::call("crm.contact.get", [
            'ID' => $contactId
        ]);

        if (isset($result['result'])) {
            return $result['result'];
        }

        return null;
    }
}
