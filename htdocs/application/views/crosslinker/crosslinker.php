<main class="contents">
    <section class="db-list">
        <div style="height:600px; overflow:auto;">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Binding Site</th>
                    <th>Cleavability</th>
                    <th>Total Mass</th>
                    <th>Mass of One Side</th>
                    <th>Mass of Cleavaged XL</th>
                    <th>Mass of the Other Side</th>
                </tr>
                <?php
                $query='select * from crosslinker';
                $crosslinker_list=$this->db->query($query)->result();
                for ($i=0;$i<count($crosslinker_list);$i++){?>
                <tr>
                    <td style="text-align:left;">
                        <?php echo $crosslinker_list[$i]->name;?>
                    </td>
                    <td>
                        <?php echo $crosslinker_list[$i]->binding_site;?>
                    </td>
                    <td>
                        <?php echo $crosslinker_list[$i]->cleavability;?>
                    </td>
                    <td>
                        <?php echo $crosslinker_list[$i]->mass;?>
                    </td>
                    <td>
                        <?php echo $crosslinker_list[$i]->mass_c;?>
                    </td>
                    <td>
                        <?php echo $crosslinker_list[$i]->mass_center;?>
                    </td>
                    <td>
                        <?php echo $crosslinker_list[$i]->mass_n;?>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>
    </section>
</main>