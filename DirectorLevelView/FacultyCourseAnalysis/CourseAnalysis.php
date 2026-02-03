<div class="faculty-analysis">
    <h1>Institute Course Analysis</h1>
    <table border='1' id='facultyTable'>
        <tr>
            <th>Metric</th>
            <td>Value</td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("course", "total")' class='range'>Total Number of Courses (Feedback Received)</th>
            <td><?php echo $instituteTotalCourses; ?></td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("course", "HS")' class='range'>Highest Scoring Course</th>
            <td><?php echo $courseRankingAnalysis['HSC']; ?> </td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("course", "LS")' class='range'>Lowest Scoring Course</th>
            <td><?php echo ($courseRankingAnalysis['LSC'] == 10) ? "0" : $courseRankingAnalysis['LSC']; ?></td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("course", "A4.5")' class='range'>Courses Scoring Above 4.5</th>
            <td><?php echo $courseRankingAnalysis['A4.5']; ?></td>
        </tr>
        <tr>
        <tr>
            <th onClick='rangeAnalysis("course", "B4.0TO4.5")' class='range'>Courses Scoring Between 4.0 and 4.5</th>
            <td><?php echo $courseRankingAnalysis['B4.0TO4.5']; ?></td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("course", "B3.5TO4")' class='range'>Courses Scoring Between 3.5 and 4.0</th>
            <td><?php echo $courseRankingAnalysis['B3.5TO4']; ?></td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("course", "B3.5")' class='range'>Courses Scoring Below 3.5</th>
            <td><?php echo $courseRankingAnalysis['B3.5']; ?></td>
        </tr>
    </table>
</div>