<div class="course-analysis">
    <h1>Institute Teacher Analysis</h1>
    <table border='1' id='courseTable'>
        <tr>
            <th>Metric</th>
            <td>Value</td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("faculty", "total")' class='range'>Total Number of Teacher</th>
            <td><?php echo count($facultyCourseWiseAverageArray); ?> </td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("faculty", "HS")' class='range'>Highest Scoring Teacher</th>
            <td><?php echo $facultyRankingAnalysis['HSF']; ?></td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("faculty", "LS")' class='range'>Lowest Scoring Teacher</th>
            <td><?php echo ($facultyRankingAnalysis['LSF'] == 10) ? "0" : $facultyRankingAnalysis['LSF']; ?></td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("faculty", "A4.5")' class='range'>Teacher Scoring Above 4.5</th>
            <td><?php echo $facultyRankingAnalysis['A4.5']; ?></td>
        </tr>

        <tr>
            <th onClick='rangeAnalysis("faculty", "B4.0TO4.5")' class='range'>Teacher Scoring Between 4.0 and 4.5</th>
            <td><?php echo $facultyRankingAnalysis['B4.0TO4.5']; ?></td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("faculty", "B3.5TO4")' class='range'>Teacher Scoring Between 3.5 and 4.0</th>
            <td><?php echo $facultyRankingAnalysis['B3.5TO4']; ?></td>
        </tr>
        <tr>
            <th onClick='rangeAnalysis("faculty", "B3.5")' class='range'>Teacher Scoring Below 3.5</th>
            <td><?php echo $facultyRankingAnalysis['B3.5']; ?></td>
        </tr>
    </table>
</div>