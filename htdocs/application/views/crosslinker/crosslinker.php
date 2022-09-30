<main class="contents">
    <section class="db-list">
        <div style="height: 600px; overflow:auto;">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Binding Site</th>
                    <th>Cleavability</th>
                    <th>Spacer Arm Length</th>
                    <th>Total Mass</th>
                    <th>Mass of c-term</th>
                    <th>Mass of center</th>
                    <th>Mass of n-term</th>
                </tr>
                <?php
                $query = 'select * from crosslinker';
                $list_of_enzyme_all = $this->db->query($query)->result();
                for ($i=0;$i<count($list_of_enzyme_all);$i++){ ?>
                <tr>
                    <td style="text-align:left;">
                        <?php  echo $list_of_enzyme_all[$i]->name; ?>
                    </td>
                    <td>
                        <?php echo($list_of_enzyme_all[$i]->binding_site); ?>
                    </td>
                    <td>
                        <?php echo($list_of_enzyme_all[$i]->cleavability); ?>
                    </td>
                    <td>
                        <?php echo($list_of_enzyme_all[$i]->spacer_arm); ?>    
                    </td>
                    <td>
                        <?php echo($list_of_enzyme_all[$i]->mass); ?>
                    </td>
                    <td>
                        <?php echo($list_of_enzyme_all[$i]->mass_c); ?>
                    </td>
                    <td>
                        <?php echo($list_of_enzyme_all[$i]->mass_center); ?>
                    </td>
                    <td>
                        <?php echo($list_of_enzyme_all[$i]->mass_n); ?>
                    </td>
                </tr>
            <?php } ?>
            </table>
        </div>
    </section>
</main>