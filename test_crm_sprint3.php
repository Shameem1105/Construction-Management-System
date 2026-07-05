<?php
/**
 * test_crm_sprint3.php - Automated CRM Sprint 3 Integration Test Runner
 * Checks the full lifecycle: Add -> Retrieve -> Update -> Revise -> Approve -> Convert
 */

require_once 'db.php';

header('Content-Type: text/plain');

echo "========================================================\n";
echo "CRM SPRINT 3 - QUOTATION & BOQ INTEGRATION TEST RUNNER\n";
echo "========================================================\n\n";

function postRequest($url, $data) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $decoded = json_decode($result, true);
    if ($decoded === null) {
        echo "DEBUG: Raw response from $url:\n$result\n";
    }
    return $decoded;
}

function getRequest($url) {
    $result = file_get_contents($url);
    return json_decode($result, true);
}

// 1. Create a mock Lead
echo "Step 1: Creating a test CRM Lead in database...\n";
$leadName = "Sprint 3 Test Lead";
$leadPhone = "+91 99999 88888";
$leadEmail = "sprint3test@jgc.com";
$leadCompany = "Sprint 3 Testing Company";
$leadAddress = "Plot 99, Phase 2, Electronic City, Bangalore";
$leadReq = "Industrial Warehouse Construction";

$leadStmt = $conn->prepare("INSERT INTO leads (name, phone, email, company, address, project_type, status) VALUES (?, ?, ?, ?, ?, ?, 'New Lead')");
$leadStmt->bind_param('ssssss', $leadName, $leadPhone, $leadEmail, $leadCompany, $leadAddress, $leadReq);
if (!$leadStmt->execute()) {
    die("FAILED: Could not create test CRM Lead: " . $leadStmt->error . "\n");
}
$leadId = $conn->insert_id;
$leadStmt->close();
echo "-> Mock Lead created with ID: $leadId\n\n";

$baseUrl = "http://localhost/jgc_constructions/api/";
$quotationId = null;
$qNumber = null;
$revisedId = null;
$projectId = null;

try {
    // 2. Add Quotation
    echo "Step 2: Testing api/add_quotation.php (Create Version 1)...\n";
    $addPayload = [
        'lead_id' => $leadId,
        'title' => "Test Warehouse Project Proposal",
        'client_name' => "Rajesh Kumar",
        'client_company' => "Sprint 3 Testing Company",
        'client_address' => "Plot 99, Phase 2, Electronic City, Bangalore",
        'subtotal' => 100000.00,
        'tax_rate' => 18.00,
        'tax_amount' => 18000.00,
        'discount' => 5000.00,
        'grand_total' => 113000.00,
        'notes' => "1. Work will start within 10 days of advance payment.\n2. Rates are fixed for 30 days.",
        'items_json' => json_encode([
            [
                'category' => 'Civil Work',
                'description' => 'Foundation excavation work',
                'unit' => 'Cum',
                'quantity' => 100.000,
                'rate' => 500.00
            ],
            [
                'category' => 'Structural',
                'description' => 'Steel structure reinforcement',
                'unit' => 'Kgs',
                'quantity' => 1000.000,
                'rate' => 50.00
            ]
        ])
    ];

    $addResp = postRequest($baseUrl . "add_quotation.php", $addPayload);
    if (!$addResp || !isset($addResp['success']) || !$addResp['success']) {
        throw new Exception("add_quotation.php failed: " . ($addResp['message'] ?? 'No response'));
    }

    $quotationId = intval($addResp['id']);
    $qNumber = $addResp['quotation_number'];
    echo "-> SUCCESS: Quotation created with ID: $quotationId, Number: $qNumber\n\n";

    // 3. Get Details
    echo "Step 3: Testing api/get_quotation_details.php (Retrieve and verify totals)...\n";
    $detResp = getRequest($baseUrl . "get_quotation_details.php?id=" . $quotationId);
    if (!$detResp || !$detResp['success']) {
        throw new Exception("get_quotation_details.php failed");
    }

    $q = $detResp['quotation'];
    $items = $detResp['items'];
    
    // Mathematical validations
    if ($q['quotation_number'] !== $qNumber) throw new Exception("Quotation number mismatch");
    if (floatval($q['subtotal']) !== 100000.00) throw new Exception("Subtotal mismatch");
    if (floatval($q['tax_amount']) !== 18000.00) throw new Exception("Tax Amount mismatch");
    if (floatval($q['grand_total']) !== 113000.00) throw new Exception("Grand Total mismatch");
    if (count($items) !== 2) throw new Exception("BOQ items count mismatch. Expected 2, got " . count($items));

    echo "-> SUCCESS: Verification check passed. Totals: Subtotal ₹100,000.00, GST 18%, Grand Total ₹113,000.00\n\n";

    // 4. Update Quotation (Change to Sent and increase items)
    echo "Step 4: Testing api/update_quotation.php (Update Draft items and status to Sent)...\n";
    $updatePayload = [
        'id' => $quotationId,
        'lead_id' => $leadId,
        'title' => "Test Warehouse Project Proposal (Finalized)",
        'client_name' => "Rajesh Kumar",
        'client_company' => "Sprint 3 Testing Company",
        'client_address' => "Plot 99, Phase 2, Electronic City, Bangalore",
        'status' => 'Sent',
        'subtotal' => 120000.00,
        'tax_rate' => 18.00,
        'tax_amount' => 21600.00,
        'discount' => 5000.00,
        'grand_total' => 136600.00,
        'notes' => $addPayload['notes'],
        'items_json' => json_encode([
            [
                'category' => 'Civil Work',
                'description' => 'Foundation excavation work',
                'unit' => 'Cum',
                'quantity' => 100.000,
                'rate' => 500.00
            ],
            [
                'category' => 'Structural',
                'description' => 'Steel structure reinforcement',
                'unit' => 'Kgs',
                'quantity' => 1000.000,
                'rate' => 50.00
            ],
            [
                'category' => 'Finishing',
                'description' => 'Brick flooring and plastering',
                'unit' => 'SqFt',
                'quantity' => 200.000,
                'rate' => 100.00
            ]
        ])
    ];

    $updResp = postRequest($baseUrl . "update_quotation.php", $updatePayload);
    if (!$updResp || !$updResp['success']) {
        throw new Exception("update_quotation.php failed: " . ($updResp['message'] ?? 'No response'));
    }
    echo "-> SUCCESS: Quotation updated and set to status 'Sent'\n\n";

    // 5. Revise Quotation
    echo "Step 5: Testing api/revise_quotation.php (Revise Sent version to Draft v2)...\n";
    $revPayload = ['id' => $quotationId];
    $revResp = postRequest($baseUrl . "revise_quotation.php", $revPayload);
    if (!$revResp || !$revResp['success']) {
        throw new Exception("revise_quotation.php failed: " . ($revResp['message'] ?? 'No response'));
    }

    $revisedId = intval($revResp['new_id']);
    echo "-> SUCCESS: New version created with ID: $revisedId\n";

    // Verify database state for both
    $checkQ1 = $conn->query("SELECT status, version FROM quotations WHERE id = $quotationId")->fetch_assoc();
    $checkQ2 = $conn->query("SELECT status, version, quotation_number FROM quotations WHERE id = $revisedId")->fetch_assoc();

    if ($checkQ1['status'] !== 'Revised') {
        throw new Exception("Original quotation status is " . $checkQ1['status'] . ", expected 'Revised'");
    }
    if ($checkQ2['status'] !== 'Draft' || intval($checkQ2['version']) !== 2) {
        throw new Exception("New revision status=" . $checkQ2['status'] . ", version=" . $checkQ2['version'] . ". Expected Draft, v2");
    }
    if ($checkQ2['quotation_number'] !== $qNumber) {
        throw new Exception("Quotation number changed on revision");
    }

    echo "-> SUCCESS: Version 1 is marked 'Revised', Version 2 is created as 'Draft' v2\n\n";

    // 6. Approve Quotation version 2
    echo "Step 6: Testing api/update_quotation.php (Approve Version 2)...\n";
    $approvePayload = $updatePayload;
    $approvePayload['id'] = $revisedId;
    $approvePayload['status'] = 'Approved';
    $appResp = postRequest($baseUrl . "update_quotation.php", $approvePayload);
    if (!$appResp || !$appResp['success']) {
        throw new Exception("Failed to approve quotation: " . ($appResp['message'] ?? ''));
    }
    echo "-> SUCCESS: Version 2 status updated to 'Approved'\n\n";

    // 7. Convert to Project
    echo "Step 7: Testing api/convert_quotation_project.php (Convert Approved quote)...\n";
    $convPayload = ['id' => $revisedId];
    $convResp = postRequest($baseUrl . "convert_quotation_project.php", $convPayload);
    if (!$convResp || !$convResp['success']) {
        throw new Exception("convert_quotation_project.php failed: " . ($convResp['message'] ?? ''));
    }

    $projectId = intval($convResp['project_id']);
    echo "-> SUCCESS: Project created with ID: $projectId\n";

    // Verify Project Location creation and Lead status update
    $checkProj = $conn->query("SELECT title, city, address FROM projects WHERE id = $projectId")->fetch_assoc();
    $checkLead = $conn->query("SELECT status FROM leads WHERE id = $leadId")->fetch_assoc();
    $checkQ2Final = $conn->query("SELECT project_id FROM quotations WHERE id = $revisedId")->fetch_assoc();

    if (!$checkProj || $checkProj['title'] !== "Test Warehouse Project Proposal (Finalized)") {
        throw new Exception("Project details mismatch or project not found");
    }
    if ($checkLead['status'] !== 'Won') {
        throw new Exception("CRM Lead status is " . $checkLead['status'] . ", expected 'Won'");
    }
    if (intval($checkQ2Final['project_id']) !== $projectId) {
        throw new Exception("Quotation project_id reference not linked");
    }

    echo "-> SUCCESS: Project Location matches quotation title, Lead transitioned to 'Won', and Quotation links to Project ID.\n\n";

    echo "========================================================\n";
    echo "SUMMARY: ALL AUTOMATED SPRINT 3 API TESTS PASSED SUCCESSFULLY!\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "\n!!! TEST FAILURE: " . $e->getMessage() . "\n";
} finally {
    // Clean up
    echo "\nStep 8: Cleaning up test records from database...\n";
    if ($quotationId) {
        $conn->query("DELETE FROM quotation_items WHERE quotation_id = $quotationId");
        $conn->query("DELETE FROM quotations WHERE id = $quotationId");
    }
    if ($revisedId) {
        $conn->query("DELETE FROM quotation_items WHERE quotation_id = $revisedId");
        $conn->query("DELETE FROM quotations WHERE id = $revisedId");
    }
    if ($projectId) {
        $conn->query("DELETE FROM projects WHERE id = $projectId");
    }
    if ($leadId) {
        $conn->query("DELETE FROM lead_activities WHERE lead_id = $leadId");
        $conn->query("DELETE FROM leads WHERE id = $leadId");
    }
    echo "-> Clean up completed.\n";
}

$conn->close();
?>
