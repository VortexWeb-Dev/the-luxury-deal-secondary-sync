<?php

require_once __DIR__ . '/../crest/crest.php';

class BitrixController
{
    // Method to add a secondary lead
    public function addLead(int $entityTypeId, array $leadData): ?array
    {
        if (empty($leadData['title'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Missing required lead field: title']);
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

    // Method to update a secondary lead
    public function updateLead(int $entityTypeId, array $leadData, int $leadId): ?array
    {
        if (empty($leadData['title'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Missing required lead field: title']);
            exit;
        }

        $result = CRest::call('crm.item.update', [
            'entityTypeId' => $entityTypeId,
            'id' => $leadId,
            'fields' => $leadData,
        ]);

        if (isset($result['result']['item'])) {
            return $result['result']['item'];
        }

        return null;
    }

    // Method to filter a secondary lead by deal ID
    public function getLeadByDealId(int $entityTypeId, int $dealId): ?array
    {
        $result = CRest::call("crm.item.list", [
            'entityTypeId' => $entityTypeId,
            'filter' => [
                'ufCrm9_1743737418' => $dealId,
            ],
            'order' => [
                'id' => 'DESC',
            ],
        ]);

        if (isset($result['result']['items'][0])) {
            return $result['result']['items'][0];
        }

        return null;
    }

    // Method to get a deal
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

    // Method to get a contact
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
