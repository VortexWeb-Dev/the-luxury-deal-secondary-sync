<?php
define('CONFIG', require_once __DIR__ . '/../config.php');

class UtilsController
{
    // Parses incoming JSON data
    public static function parseRequestData(): ?array
    {
        if (!empty($_POST)) {
            return $_POST;
        }

        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        return $data;
    }

    // Sends response back to the webhook
    public static function sendResponse(int $statusCode, array $data): void
    {
        header("Content-Type: application/json");
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    // Checks if the deal is in the correct stage
    public static function isValidDeal(array $deal): bool
    {
        return isset($deal['STAGE_ID']) && $deal['STAGE_ID'] === CONFIG['DIRECTORS_APPROVAL_STAGE_ID'];
    }

    // Map deal bedrooms to their respective IDs
    public static function getBedroomsId($dealBedrooms): ?int
    {
        $bedroomsId = null;
        switch ($dealBedrooms) {
            case 68:
                $bedroomsId = 1580;
                break;
            case 69:
                $bedroomsId = 1581;
                break;
            case 70:
                $bedroomsId = 1582;
                break;
            case 71:
                $bedroomsId = 1583;
                break;
            case 72:
                $bedroomsId = 1584;
                break;
            case 73:
                $bedroomsId = 1585;
                break;
            case 74:
                $bedroomsId = 1586;
                break;
            case 75:
                $bedroomsId = 1587;
                break;
            default:
                $bedroomsId = null;
        }
        return $bedroomsId;
    }

    // Map deal unit status to their respective IDs
    public static function getUnitStatusId($dealUnitStatus): ?int
    {
        $unitStatusId = null;
        switch ($dealUnitStatus) {
            case 925: // Rented
                $unitStatusId = 1579;
                break;
            case 926: // Vacant
                $unitStatusId = 1576;
                break;

            default:
                $unitStatusId = null;
        }
        return $unitStatusId;
    }

    // Map deal unit status to their respective IDs
    public static function geManagerApprovalId($dealManagerApproval): ?int
    {
        $managerApprovalId = null;
        switch ($dealManagerApproval) {
            case 897: // Approved
                $managerApprovalId = 1590;
                break;
            case 899: // Rejected
                $managerApprovalId = 1591;
                break;

            default:
                $managerApprovalId = null;
        }
        return $managerApprovalId;
    }

    // Map deal property type to their respective IDs
    public static function getPropertyTypeId($dealPropertyType): ?int
    {
        $propertyTypeId = null;
        switch ($dealPropertyType) {
            case 989: // Apartment
                $propertyTypeId = 1559;
                break;
            case 990: // Villa
                $propertyTypeId = 1560;
                break;
            case 991: // Townhouse
                $propertyTypeId = 1563;
                break;
            case 992: // Penthouse
                $propertyTypeId = 1564;
                break;
            case 993: // Office
                $propertyTypeId = 2021;
                break;
            case 994: // Plot
                $propertyTypeId = 1561;
                break;
            case 995: // Building
                $propertyTypeId = 1562;
                break;
            case 996: // Duplex
                $propertyTypeId = 2022;
                break;
            case 997: // Triplex
                $propertyTypeId = 2023;
                break;

            default:
                $propertyTypeId = null;
        }
        return $propertyTypeId;
    }

    // Map deal unit purpose to their respective IDs
    public static function getUnitPurposeId($dealUnitPurpose): ?int
    {
        $unitPurposeId = null;
        switch ($dealUnitPurpose) {
            // To Do

            default:
                $unitPurposeId = null;
        }
        return $unitPurposeId;
    }
}
