NOTES:

- Student ID value is hardcoded but everything is fetched using the provided student id (need to be updated into automatically fetching when logging in by checking the credentials cross referencing it for its student_id).

Currently enrolled subjects work by:

  1. Identify the Current Semester ID 🗓️
  The script first checks the semester table.
  It looks for the record where today's date falls between the date_start and date_end.
  This determines the sem_id (e.g., S-2025-2).
  
  2. Get the Subject Code 📅
  The script uses the sem_id found in Step 1 to search the schedule table.
  It retrieves all the entries (courses, times, etc.) that match that specific sem_id.
  From those entries, it isolates the sub_code (e.g., CS-201).
  
  3. Retrieve the Subject Name 📘
  The script uses the sub_code found in Step 2 to search the subjects table.
  It finds the single entry that matches that specific sub_code.
  From that entry, it pulls the corresponding subject name (e.g., "Data Structures and Algorithms").
  This chain of checks allows the system to accurately translate a current semester into a list of specific, readable course titles.

To work html extension should be changed into php tehn add the following to the top:
<?php 
// This must be at the top of the file to ensure variables are defined
require 'studentprofile.php'; 
?>

and then change the following:
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

I'll also upload the updated html-php file.
