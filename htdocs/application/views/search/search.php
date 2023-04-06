        <main class="contents">
            <section class="search">
                <p>Search Protein Interaction</p>
                <br>
                <br>
                <form method="POST" class="searchbox" action="/search/result">
                    <div>
                        <div>
                            <ul>
                                <li>
                                    <input type="hidden" name="page_now" value="<?php
                                    if (empty($page_now)){
                                        echo 1;
                                    } else{
                                        echo $page_now;
                                    }
                                    ?>">
                                </li>
                                <li>
                                    <label for="protein_id">
                                        <p>Protein :</p>
                                        <input type="text" name="human_protein_reviewed" id="protein_id" placeholder="Please enter the entry only. Please check isoform of human protein what out DB have on top of menu." required>
                                    </label>
                                </li>
                                <li>
                                    <label for="enzyme">
                                        <p>Enzyme :</p>
                                        <select name="enzyme">
                                            <?php
                                            foreach ($enzyme as $row){
                                                echo '<option value="'.$row->id.'">'.$row->name.'</option>';
                                            }?>
                                        </select>
                                    </label>
                                </li>
                                <li>
                                    <label for="crosslinker_id">
                                        <p>Cross-Linker :</p>
                                        <select name="crosslinker">
                                            <?php
                                            foreach($crosslinker as $row){
                                                echo '<option value="'.$row->id.'">'.$row->name.'</option>';
                                            }
                                            ?>
                                        </select>
                                    </label>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <ul>
                                <li>
                                    <label for="peptide_length_min">
                                        <p>Peptide Length Range (Min):</p>
                                        <input type="number" name="peptide_length_min" placeholder="How long peptides would you like to see?" required>
                                    </label>
                                </li>
                                <li>
                                    <label for="peptide_length_max">
                                        <p>Peptide Length Range (Max):</p>
                                        <input type="number" name="peptide_length_max" placeholder="How long peptides would you like to see?" required>
                                    </label>
                                </li>
                                <li>
                                    <label for="rank">
                                        <p>Number of Interaction Ranking:</p>
                                        <input type="number" name="sorting" placeholder="How many interaction would you like to see?" required>
                                    </label>
                                </li>
                                <li>
                                    <label for="peptidecharge">
                                        <p>Peptide Charge</p>
                                        <label for="peptide_charge_1">
                                            1<input id="peptide_charge_1" type="checkbox" name="peptidecharge[]" value="1">
                                        </label>
                                        <label for="peptide_charge_2">
                                            2<input id="peptide_charge_2" type="checkbox" name="peptidecharge[]" value="2">
                                        </label>
                                        <label for="peptide_charge_3">
                                            3<input id="peptide_charge_3" type="checkbox" name="peptidecharge[]" value="3">
                                        </label>
                                        <label for="peptide_charge_4">
                                            4<input id="peptide_charge_4" type="checkbox" name="peptidecharge[]" value="4">
                                        </label>
                                        <label for="peptide_charge_5">
                                            5<input id="peptide_charge_5" type="checkbox" name="peptidecharge[]" value="5">
                                        </label>
                                        <label for="peptide_charge_6">
                                            6<input id="peptide_charge_6" type="checkbox" name="peptidecharge[]" value="6">
                                        </label>
                                        <label for="peptide_charge_7">
                                            7<input id="peptide_charge_7" type="checkbox" name="peptidecharge[]" value="7">
                                        </label>
                                        <label for="peptide_charge_8">
                                            8<input id="peptide_charge_8" type="checkbox" name="peptidecharge[]" value="8">
                                        </label>
                                        <label for="peptide_charge_9">
                                            9<input id="peptide_charge_9" type="checkbox" name="peptidecharge[]" value="9">
                                        </label>
                                        <label for="peptide_charge_10">
                                            10<input id="peptide_charge_10" type="checkbox" name="peptidecharge[]" value="10">
                                        </label>
                                    </label>
                                </li>
                                <li>
                                    <label for="ioncharge">
                                        <p>Ion Charge</p>
                                        <label for="ion_charge_1">
                                            1<input id="ion_charge_1" type="checkbox" name="ioncharge[]" value="1">
                                        </label>
                                        <label for="ion_charge_2">
                                            2<input id="ion_charge_2" type="checkbox" name="ioncharge[]" value="2">
                                        </label>
                                        <label for="ion_charge_3">
                                            3<input id="ion_charge_3" type="checkbox" name="ioncharge[]" value="3">
                                        </label>
                                        <label for="ion_charge_4">
                                            4<input id="ion_charge_4" type="checkbox" name="ioncharge[]" value="4">
                                        </label>
                                        <label for="ion_charge_5">
                                            5<input id="ion_charge_5" type="checkbox" name="ioncharge[]" value="5">
                                        </label>
                                        <label for="ion_charge_6">
                                            6<input id="ion_charge_6" type="checkbox" name="ioncharge[]" value="6">
                                        </label>
                                        <label for="ion_charge_7">
                                            7<input id="ion_charge_7" type="checkbox" name="ioncharge[]" value="7">
                                        </label>
                                        <label for="ion_charge_8">
                                            8<input id="ion_charge_8" type="checkbox" name="ioncharge[]" value="8">
                                        </label>
                                        <label for="ion_charge_9">
                                            9<input id="ion_charge_9" type="checkbox" name="ioncharge[]" value="9">
                                        </label>
                                        <label for="ion_charge_10">
                                            10<input id="ion_charge_10" type="checkbox" name="ioncharge[]" value="10">
                                        </label>
                                    </label>
                                </li>
                            </ul>
                        </div>
                        <div class="modifications">
                            <ul>
                                <?php echo '<p>Modifications</p>';
                                for ($i=0;$i<count($modification);$i++){
                                    echo '<li>';
                                    echo '<p>';
                                    echo $modification[$i]->name.'('.$modification[$i]->str.')';
                                    echo '<p>';
                                    echo '<div>';
                                    echo '<label for="'.$modification[$i]->name.'_none">';
                                    echo 'None <input id="'.$modification[$i]->name.'_none" type="radio" name="'.$modification[$i]->name.'" value="N" checked>';
                                    echo '</label>';
                                    echo '<label for="'.$modification[$i]->name.'_static">';
                                    echo 'Static <input id="'.$modification[$i]->name.'_static" type="radio" name="'.$modification[$i]->name.'" value="S">';
                                    echo '</label>';
                                    echo '<label for="'.$modification[$i]->name.'_variable">';
                                    echo 'variable <input id="'.$modification[$i]->name.'_variable" type="radio" name="'.$modification[$i]->name.'" value="V">';
                                    echo '</label>';
                                    echo '</div>';
                                    echo '</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <div>
                        <button type="submit">Search</button>
                    </div>
                </form>
            </section>
        </main>