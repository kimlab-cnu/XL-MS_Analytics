    <main class="contents">
        <section class="db-list">
            <div style="height:600px; overflow:auto;">
                <table>
                    <tr>
                        <th>Entry Number</th>
                        <th>Entry Name</th>
                        <th>STRING</th>
                        <th>SequenceID Length</th>
                    </tr>
                    <?php
                    $query='select * from human_protein_reviewed';
                    $humanprotin_list=$this->db->query($query)->result();
                    for ($i=0;$i<count($humanprotin_list);$i++){
                    ?>
                    <tr>
                        <td>
                            <?php echo($humanprotin_list[$i]->entrynumber);?>
                        </td>
                        <td>
                            <?php echo($humanprotin_list[$i]->entryname);?>
                        </td>
                        <td>
                            <?php echo($humanprotin_list[$i]->string);?>
                        </td>
                        <td>
                            <?php echo($humanprotin_list[$i]->sequenceID_length);?>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
        </section>
    </main>