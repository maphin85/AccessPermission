<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["checkPassword"])) {
    header("Content-Type: application/json");
    $response = ["success" => false, "message" => "Unknown error"];

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=test", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $modKey = $_POST["password"] ?? "";
        $visitorId = $_POST["visitorId"] ?? "";

        $stmt = $pdo->prepare("SELECT * FROM modkey WHERE ModKey = ?");
        $stmt->execute([$modKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $response["message"] = "Password is invalid.";
        } else {
            $device1 = $row["DeviceID1"];
            $device2 = $row["DeviceID2"];
            $duration = intval($row["Duration"]);
            $startDate = $row["StartDateTime"];
            $expireDate = $row["ExpireDateTime"];

            if (empty($device1) && empty($device2)) {
                // First time usage
                $startDateTime = date("Y-m-d H:i:s");
                $expireDateTime = date("Y-m-d H:i:s", strtotime("+$duration days"));

                $update = $pdo->prepare("UPDATE modkey SET DeviceID1 = ?, DeviceID2 = ?, StartDateTime = ?, ExpireDateTime = ? WHERE ModKey = ?");
                $update->execute([$visitorId, $visitorId, $startDateTime, $expireDateTime, $modKey]);

                $response["success"] = true;
                $response["message"] = "Access Granted. Mod is working!";
            } else {
                if ($visitorId === $device2) {
                    if (strtotime($expireDate) < time()) {
                        // Expired
                        $delete = $pdo->prepare("DELETE FROM modkey WHERE ModKey = ?");
                        $delete->execute([$modKey]);
                        $response["message"] = "Password was expired, Please buy new password.";
                    } else {
                        $response["success"] = true;
                        $response["message"] = "Access Granted. Mod is working!";
                    }
                } else {
                    $response["message"] = "Password is correct but it is for other user. Please buy key to use.";
                }
            }
        }
    } catch (PDOException $e) {
        $response["message"] = "Database error: " . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
?>
