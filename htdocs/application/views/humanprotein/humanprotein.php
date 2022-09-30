    <main class="contents">
        <section class="db-list">
            <div style="height: 600px; overflow:auto;">
                <table>
                    <tr>
                        <th>Entry Number</th>
                        <th>Entry Name</th>
                        <th>STRING</th>
                        <th>SequenceID Length</th>
                    </tr>
                    <?php
                    $query = 'select * from human_protein_reviewed';
                    $list_of_human_protein = $this->db->query($query)->result();
                    for ($i=0;$i<count($list_of_human_protein);$i++){
                    ?>
                    <tr>
                        <td>
                            <?php echo($list_of_human_protein[$i]->entrynumber); ?>
                        </td>
                        <td>
                            <?php echo($list_of_human_protein[$i]->entryname); ?>
                        </td>
                        <td>
                            <?php echo($list_of_human_protein[$i]->string); ?>    
                        </td>
                        <td>
                            <?php echo($list_of_human_protein[$i]->sequenceID_length); ?>
                        </td>
                    </tr>
                <?php } ?>
                </table>
            </div>
        </section>
    </main>