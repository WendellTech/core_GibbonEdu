<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Services\Format;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/firstAidRecord_add.php&gibbonFormGroupID='.$_GET['gibbonFormGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'];

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_add.php') == false) {
    $URL .= '&return=error0&step=1';
    header("Location: {$URL}");
} else {
    $gibbonFirstAidID = null;
    if (isset($_POST['gibbonFirstAidID'])) {
        $gibbonFirstAidID = $_POST['gibbonFirstAidID'];
    }

    //Proceed!
    $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
    $gibbonPersonIDFirstAider = $session->get('gibbonPersonID');
    $date = $_POST['date'] ?? '';
    $timeIn = $_POST['timeIn'] ?? '';
    $description = $_POST['description'] ?? '';
    $actionTaken = $_POST['actionTaken'] ?? '';
    $followUp = $_POST['followUp'] ?? '';

    if ($gibbonPersonID == '' or $gibbonPersonIDFirstAider == '' or $date == '' or $timeIn == '') {
        $URL .= '&return=error1&step=1';
        header("Location: {$URL}");
    } else {
        $customRequireFail = false;
        $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('First Aid', [], $customRequireFail);

        if ($customRequireFail) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        //Write to database
        try {
            $data = array('gibbonPersonIDPatient' => $gibbonPersonID, 'gibbonPersonIDFirstAider' => $gibbonPersonIDFirstAider, 'date' => Format::dateConvert($date), 'timeIn' => $timeIn, 'description' => $description, 'actionTaken' => $actionTaken, 'followUp' => $followUp, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'fields' => $fields);
            $sql = 'INSERT INTO gibbonFirstAid SET gibbonPersonIDPatient=:gibbonPersonIDPatient, gibbonPersonIDFirstAider=:gibbonPersonIDFirstAider, date=:date, timeIn=:timeIn, description=:description, actionTaken=:actionTaken, followUp=:followUp, gibbonSchoolYearID=:gibbonSchoolYearID, fields=:fields';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=erorr2&step=1';
            header("Location: {$URL}");
            exit();
        }

        //Last insert ID
        $AI = str_pad($connection2->lastInsertID(), 12, '0', STR_PAD_LEFT);

        $URL .= "&return=success0&editID=$AI";
        header("Location: {$URL}");
    }
}
