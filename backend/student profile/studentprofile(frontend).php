<?php 
// This must be at the top of the file to ensure variables are defined
require 'studentprofile.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Student Enrollment</title>
    <link rel="stylesheet" href="ads-css.css">
</head>

<style>
        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background-image: url('https://plm.edu.ph/FooterImage.jpg');
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
        }

        .header {
            max-width: 100%;
            max-height: 60px;
            padding: 15px 35px;
            background-color: white;
            display: flex;
            flex-direction: row;
            gap: 15px;
            box-shadow: 0px 1px 10px 1px#52525286;
            align-items: center;
        }

        .header p {
            font-family: 'Times New Roman', Times, serif; 
            color: #b18819; 
            font-size: 25px; 
            font-weight: bold; 
            vertical-align: middle; 
        }

        .header img {
            width: 60px;
            height: 60px;
        }

        .tabsBtn {
            align-items: center;
            display: flex;
            flex-direction: row;
            position: absolute;
            right: 2%;
            gap: 8px;
        }

        .tabs-btn { 
            display: flex;
            font-size: 22px;
            position: relative;
            display: inline-flex;
            text-align: center;
            background-color: transparent;
            color: 080808;
            padding: 10px 20px;
            border-width: 0px;
            cursor: pointer;
        }

        .tabs-btn::after {
            content: '';
            position: absolute;
            align-self: center;
            left: 0;
            right: 0;
            bottom: -5px; 
            width: 0;
            height: 2px;
            background-color: #b18819; 
            text-decoration-thickness: 3px;
            transition: width 0.6s ease-in-out; 
        }

        .tabs-btn:hover::after {
            width: 100%; 
        }

        .tabs-btn:hover {
            color: #b18819;
        }

        .container {
            background-color: #ffffff;
            max-width: 95%;       
            margin: 0;            
            position: absolute;
            top: 130px;
            left: 50%;
            transform: translate(-50%, 0%);
            padding: 30px 35px;       
            border-radius: 30px;
            text-align: left;
            align-items: flex-start;
            box-shadow: 0px 1px 10px 1px #52525286;        
        }

        /* Student Info Styles */
        .student-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            width: 100%;
            gap: 30px;
        }

        .info {
            flex: 1;
        }

        .info table {
            width: 100%;
        }

        .info td.label {
            font-weight: bold;
            width: 180px;
            padding: 5px 0;
            text-align: left;
        }

        .photo-box {
            width: 150px;
            height: 150px;
            border: 2px solid #b3b3b3;
            border-radius: 4px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            background-color: #e6e6e6;
            flex-shrink: 0;
        }

        .photo-box img {
            width: 100%;
            height: auto;
        }

        .placeholder {
            color: #888;
            font-style: italic;
        }

        /*  SIGN OUT POP UP */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(3px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .popup-box {
            background: white;
            width: 320px;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.2);
        }

        .popup-logo { 
            width: 60px; 
            height: 60px; 
            margin-bottom: 10px; 
            object-fit: contain; 
        }

        .popup-box h3 { 
            margin: 5px 0 10px 0; 
            font-size: 22px; 
            color: #080808; 
        }

        .popup-box p { 
            font-size: 14px; 
            color: #444; 
            margin-bottom: 20px; 
        }

        .popup-btn {
            width: 50%;
            padding: 12px;
            border-radius: 13px;
            border: none;
            font-size: 15px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .signout-btn { 
            background-color: #8a6912; 
            color: white; 
        }

        .signout-btn:hover { 
            background-color: #b18819;
 
        }

        .cancel-btn { 
            background-color: #CBCBCB; 
            color: #000; 
        }

        .cancel-btn:hover { 
            background-color: #e2e6eb; 
        }
             /* TABLES */
        table {
            max-width: 100%;
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 16px;
            margin-bottom: 50px;
            text-align: center;
        }

        th {
            background-color: #BF1A1A;
            color: white;
            padding: 8px;
        }

        td {
            padding: 8px;
            background: #f7f7f7;
            border-bottom: 1px solid #ccc;
            text-align: left;
        }
    
</style>

<body>

    <div class="header">
        <img src="https://plm.edu.ph/assets/plm-logo.DLcRDINN.png">
        <p>PAMANTASAN NG LUNGSOD NG MAYNILA</p>  

        <div class="tabsBtn">
            <button class="tabs-btn" onclick="openDashboard()">Dashboard</button>
            <button style = "color:#b18819" class="tabs-btn" onclick="openProfile()">Student Profile</button>
            <button class="tabs-btn" onclick="openGrades()">View Grades</button>
            <button class="tabs-btn" onclick="openEnrollment()">Enrollment</button>
            <button class="tabs-btn" onclick="openSignoutPopup()">Sign Out</button>        </div>
        </div>

        <!-- MAIN PANEL -->
        <div class="container">
            <div class="student-info">
                <div class="info">
                    <h2>Student Information</h2>
                    <table>
                        <tr>
                            <td class="label">Student ID No.</td>
                            <td>: <span class="<?php echo $placeholder_class; ?>" id="studentId"><?php echo $student_id; ?></span></td>
                        </tr>
                        <tr>
                            <td class="label">Student Name</td>
							<td>: <span class="<?php echo $placeholder_class; ?>" id="studentName"><?php echo $student_name; ?></span></td>
                        </tr>
                        <tr>
                            <td class="label">Program/Degree</td>
							<td>: <span class="<?php echo $placeholder_class; ?>" id="programDegree"><?php echo $program_name; ?></span></td>
                        </tr>
                        <tr>
                            <td class="label">Year Level</td>
							<td>: <span class="<?php echo $placeholder_class; ?>" id="yearLevel"><?php echo $yrlevel; ?></span></td>
                        </tr>
                        <tr>
                            <td class="label">Registration Status</td>
							<td>: <span class="<?php echo $placeholder_class; ?>" id="regStatus"><?php echo $status_name; ?></span></td>
                        </tr>
                    </table>
                </div>
                <div class="profile photo-box">
                    <img src="https://i.pinimg.com/originals/ad/73/1c/ad731cd0da0641bb16090f25778ef0fd.jpg?fbclid=IwY2xjawOcx4NleHRuA2FlbQIxMQBzcnRjBmFwcF9pZAEwAAEeNHq49yNbtWnzz09Ul-SFOpVkL5rW997QIPwSi3jdDezlC8X66BpBrP-7TKw_aem_iKA067Kn9JYsTsYNB5hovw" alt="Photo Placeholder">
                </div>
            </div>

            <hr>

            <!-- subjects enrolled for current semester -->
            <h3 style="text-align: center;">SUBJECTS ENROLLED FOR CURRENT SEMESTER</h3>

			<div class="table-responsive">
				<table id="subjectsEnrolledTable" class="table table-bordered">
					<thead>
						<tr>
							<th>SUBJECT CODE</th>
							<th>SUBJECT TITLE</th>
						</tr>
					</thead>
					<tbody>
						<?php
						// Check if a current semester was successfully identified
						if ($current_sem_id === null) {
							// Case 1: No active semester found
							?>
							<tr>
								<td colspan="2" style="text-align: center; color: #BF1A1A; font-weight: bold;">
									Error: Could not determine the current active semester.
								</td>
							</tr>
							<?php
						} elseif ($subjects_result === null || $subjects_result->num_rows === 0) {
							// Case 2: Active semester found, but student is not enrolled (or subject list is empty)
							?>
							<tr>
								<td colspan="2" style="text-align: center; color: #444; font-weight: bold;">
									No subjects currently enrolled for the active semester.
								</td>
							</tr>
							<?php
						} else {
							// Case 3: Subjects found - Loop through the fetched results
							while ($row = $subjects_result->fetch_assoc()) {
								?>
								<tr>
									<td><?php echo htmlspecialchars($row['sub_code']); ?></td>
									<td><?php echo htmlspecialchars($row['subject_title']); ?></td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>
			</div>
    

    <script>
        // TAB BUTTONS
        /* NOTE: REPLACE the destination of studentProfile TO login_wPass
           when confirmSignout() is called if you will proceed with the login_wPass.html
           in final implementation */
        function openProfile() { window.location.href = "studentProfile.html"; }
        function openGrades() { window.location.href = "viewGrades.html"; }
        function openEnrollment() { window.location.href = "enrollment_1.html"; }
        function openSignoutPopup() { document.getElementById("signoutPopup").style.display = "flex"; }
            function closePopup() { document.getElementById("signoutPopup").style.display = "none"; }    
            function confirmSignout() { window.location.href = "logIn_noPass.html"; }       
    </script>

</body>
</html>
