        <main class="contents">
            <section class="result">
                <div>
                    <div>
                        <div class="identify_ppis">
                            <p>The protein you are looking for</p>
                            <h4><?php echo $search_protein;?></h4>
                            <p>interaction with</p>
                            <table>
                                <tr>
                                    <th>Name of Interacted Reviewed Human Protein</th>
                                    <th>Interaction Probability</th>
                                </tr>
                                <?php for ($i=0;$i<count($interaction_info);$i++){?>
                                <tr>
                                    <td>
                                        <?php echo $interaction_info[$i]['name'];?>
                                    </td>
                                    <td>
                                        <?php echo $interaction_info[$i]['score']/1000;?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </table>
                        </div>
                        <div class="column_description">
                            <h4>Columns Description</h4>
                            <table>
                                <tr>
                                    <th>Column</th>
                                    <th>Description</th>
                                    <th>Column</th>
                                    <th>Description</th>
                                </tr>
                                <tr>
                                    <td>Score</td>
                                    <td>Probability score which protein interaction with target protein</td>
                                    <td>Protein A' Ion</td>
                                    <td>Ion Sequence after fragmented Protein A by collision energy on MS2</td>
                                </tr>
                                <tr>
                                    <td>Protein A' Peptide</td>
                                    <td>Peptide Sequence after digested protein A by enzyme</td>
                                    <td>Protein A' Ion type</td>
                                    <td>Type of Protein A' Ion</td>
                                </tr>
                                <tr>
                                    <td>Cross Linker</td>
                                    <td><?php echo $crosslinker->name;?></td>
                                    <td>Protein B' Ion</td>
                                    <td>Ion Sequence after fragmented Protein B by collision energy on MS2</td>
                                </tr>
                                <tr>
                                    <td>Protein B' Peptide</td>
                                    <td>Peptide Sequence after digested Protein B by enzyme</td>
                                    <td>Protein B' Ion type</td>
                                    <td>Type of Protein B' Ion</td>
                                </tr>
                                <tr>
                                    <td>Precursor Charge</td>
                                    <td>Charge values on MS1</td>
                                    <td>Product Charge</td>
                                    <td>Charge values on MS3</td>
                                </tr>
                                <tr>
                                    <td>Precursor m/z<br>(Mass A)</td>
                                    <td>Mass value for Protein A linked with one side of XL</td>
                                    <td>Product m/z<br>(Mass A)</td>
                                    <td>Mass value for Protein A Ion linked with one side of XL</td>
                                </tr>
                                <tr>
                                    <td>Precursor m/z<br>(Mass B)</td>
                                    <td>Mass value for XL after cleavage by collision energy on MS2</td>
                                    <td>Product m/z<br>(Mass B)</td>
                                    <td>Mass value for XL after cleavage by collision energy on MS2</td>
                                </tr>
                                <tr>
                                    <td>Precursor m/z<br>(Mass C)</td>
                                    <td>Mass value for Protein B linked with the other side of XL</td>
                                    <td>Product m/z<br>(Mass C)</td>
                                    <td>Mass value for Protein B Ion linked with the other side of XL</td>
                                </tr>
                                <tr>
                                    <td>Precursor m/z<br>(Mass D)</td>
                                    <td>Mass value for Protein A linked with the other side of XL</td>
                                    <td>Product m/z<br>(Mass D)</td>
                                    <td>Mass value for Protein A Ion linked with the other side of XL</td>
                                </tr>
                                <tr>
                                    <td>Precursor m/z<br>(Mass E)</td>
                                    <td>Mass value for XL after cleavage by collision energy on MS2</td>
                                    <td>Product m/z<br>(Mass E)</td>
                                    <td>Mass value for XL after cleavage by collision energy on MS2</td>
                                </tr>
                                <tr>
                                    <td>Precursor m/z<br>(Mass F)</td>
                                    <td>Mass value for Protein B linked with one side of XL</td>
                                    <td>Product m/z<br>(Mass F)</td>
                                    <td>Mass value for Protein B Ion linked with one side of XL</td>
                                </tr>
                                <tr>
                                    <td colspan="2">※ if in case of Mass A is linked with the right side of XL, in case Mass C is linked with the left side of XL</td>
                                    <td colspan="2">※ Mass B is cleavaged XL mass after MS2 step</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div>
                        <form id="searchForm" method="POST" action="/search/result">
                            <input type="hidden" name="human_protein_reviewed" value="<?php echo $input_info['human_protein'];?>">
                            <input type="hidden" name="enzyme" value="<?php echo $input_info['enzyme'];?>">
                            <input type="hidden" name="crosslinker" value="<?php echo $input_info['crosslinker'];?>">
                            <input type="hidden" name="peptide_length_min" value="<?php echo $input_info['peptide_length_min'];?>">
                            <input type="hidden" name="peptide_length_max" value="<?php echo $input_info['peptide_length_max'];?>">
                            <input type="hidden" name="sorting" value="<?php echo $input_info['ranking'];?>">
                            <input type="hidden" name="carbamidomethyl" value="<?php echo $input_info['carbamidomethyl'];?>">
                            <input type="hidden" name="oxidation" value="<?php echo $input_info['oxidation'];?>">
                            <?php 
                            for ($i=0;$i<count($input_info['peptidecharge']);$i++){
                                echo '<input type="hidden" name="peptidecharge[]" value="'.$input_info['peptidecharge'][$i].'"/>';
                            }
                            for ($i=0;$i<count($input_info['ioncharge']);$i++){
                                echo '<input type="hidden" name="ioncharge[]" value="'.$input_info['ioncharge'][$i].'"/>';
                            }?>
                            <input id="pageNow" type="hidden" name="page_now" value="<?php echo $input_info['page_now'];?>">
                            <div>
                                <h4>Setting</h4>
                                <div>
                                    <div class="summary_score">
                                        <h4>Min of Interaction Probability</h4>
                                        <input type="number" step="0.001" min=0 max=1 name="score_min" value="<?php if (!empty($input_info['score_min'])) {echo $input_info['score_min'];}?>">
                                        <h4>Max of Interaction Probability</h4>
                                        <input type="number" step="0.001" min=0 max=1 name="score_max" value="<?php if (!empty($input_info['score_max'])) {echo $input_info['score_max'];}?>">
                                    </div>
                                    <div class="combined_score">
                                        <p>Highest Confidence of Interaction Probability : > 0.9</p>
                                        <p>High Confidence of Interaction Probability : > 0.7</p>
                                        <p>Medium Confidence of Interaction Probability : > 0.4</p>
                                        <p>Low Confidence of Interaction Probability : > 0.15</p>
                                    </div>
                                </div>
                                <div>
                                    <div class="summary_prego">
                                        <h4>Peptide List : Target Protein</h4>
                                        <input type="text" name="p1_hp_peptide" id="p1_peptide_str" value="<?php echo $input_info['p1_hp_peptide'];?>">
                                        <h4>Peptide List : Interacted Proteins</h4>
                                        <input type="text" name="p2_hp_peptide" id="p2_peptide_str" value="<?php echo $input_info['p2_hp_peptide'];?>">
                                    </div>
                                </div>
                                <div class="submit_research">
                                    <button type="submit">UPDATE</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="calculate_mass">
                        <table>
                            <tr>
                                <th>Index</th>
                                <th>Score</th>
                                <th>Protein A' Peptide</th>
                                <th>Protein B' Peptide</th>
                                <th>Precurosr Charge</th>
                                <th>Precursor m/z<br>(Mass A)</th>
                                <th>Precursor m/z<br>(Mass B)</th>
                                <th>Precursor m/z<br>(Mass C)</th>
                                <th>Precursor m/z<br>(Mass D)</th>
                                <th>Precursor m/z<br>(Mass E)</th>
                                <th>Precursor m/z<br>(Mass F)</th>
                                <th>Protein A' Ion</th>
                                <th>Protein A'<br>Ion type</th>
                                <th>Protein B' Ion</th>
                                <th>Protein B'<br>Ion type</th>
                                <th>Ion Charge</th>
                                <th>Product m/z<br>(Mass A)</th>
                                <th>Product m/z<br>(Mass B)</th>
                                <th>Product m/z<br>(Mass C)</th>
                                <th>Product m/z<br>(Mass D)</th>
                                <th>Product m/z<br>(Mass E)</th>
                                <th>Product m/z<br>(Mass F)</th>
                            </tr>
                            <?php for ($i=0;$i<count($result);$i++){?>
                            <tr>
                                <td><?php echo $result[$i]['id'];?></td>
                                <td><?php echo $result[$i]['combined_score']/1000;?></td>
                                <td><?php echo $result[$i]['protein1_peptide'];?></td>
                                <td><?php echo $result[$i]['protein2_peptide'];?></td>
                                <td><?php echo $result[$i]['peptidecharge'];?></td>
                                <td><?php echo $result[$i]['protein1_peptide_c_term_mass'];?></td>
                                <td><?php echo $result[$i]['center_mass_peptide_1'];?></td>
                                <td><?php echo $result[$i]['protein2_peptide_n_term_mass'];?></td>
                                <td><?php echo $result[$i]['protein1_peptide_n_term_mass'];?></td>
                                <td><?php echo $result[$i]['center_mass_peptide_2'];?></td>
                                <td><?php echo $result[$i]['protein2_peptide_c_term_mass'];?></td>
                                <td><?php echo $result[$i]['protein1_ion'];?></td>
                                <td><?php echo $result[$i]['protein1_ion_type'];?></td>
                                <td><?php echo $result[$i]['protein2_ion'];?></td>
                                <td><?php echo $result[$i]['protein2_ion_type'];?></td>
                                <td><?php echo $result[$i]['ioncharge'];?></td>
                                <td><?php echo $result[$i]['protein1_ion_c_term_mass'];?></td>
                                <td><?php echo $result[$i]['center_mass_ion_1'];?></td>
                                <td><?php echo $result[$i]['protein2_ion_n_term_mass'];?></td>
                                <td><?php echo $result[$i]['protein1_ion_n_term_mass'];?></td>
                                <td><?php echo $result[$i]['center_mass_ion_2'];?></td>
                                <td><?php echo $result[$i]['protein2_ion_c_term_mass'];?></td>
                            </tr>
                            <?php }?>
                        </table>
                    </div>
                    <form id="formResultPagination" method="POST" action="/search/result"><!-- pagination -->
                        <input type="hidden" name="human_protein_reviewed" value="<?php echo $input_info['human_protein'];?>">
                        <input type="hidden" name="enzyme" value="<?php echo $input_info['enzyme'];?>">
                        <input type="hidden" name="crosslinker" value="<?php echo $input_info['crosslinker'];?>">
                        <input type="hidden" name="peptide_length_min" value="<?php echo $input_info['peptide_length_min'];?>">
                        <input type="hidden" name="peptide_length_max" value="<?php echo $input_info['peptide_length_max'];?>">
                        <input type="hidden" name="sorting" value="<?php echo $input_info['ranking'];?>">
                        <input type="hidden" name="carbamidomethyl" value="<?php echo $input_info['carbamidomethyl'];?>">
                        <input type="hidden" name="oxidation" value="<?php echo $input_info['oxidation'];?>">
                        <input type="hidden" step="0.001" min=0 max=1 name="score_min" value="<?php if (!empty($input_info['score_min'])) {echo $input_info['score_min'];}?>">
                        <input type="hidden" step="0.001" min=0 max=1 name="score_max" value="<?php if (!empty($input_info['score_max'])) {echo $input_info['score_max'];}?>">
                        <input type="hidden" name="p1_hp_peptide" value="<?php echo $input_info['p1_hp_peptide']; ?>"/>
                        <input type="hidden" name="p2_hp_peptide" value="<?php echo $input_info['p2_hp_peptide']; ?>"/>
                        <?php
                        for ($i=0;$i<count($input_info['peptidecharge']);$i++){
                            echo '<input type="hidden" name="peptidecharge[]" value="'.$input_info['peptidecharge'][$i].'"/>';
                        }
                        for ($i=0;$i<count($input_info['ioncharge']);$i++){
                            echo '<input type="hidden" name="ioncharge[]" value="'.$input_info['ioncharge'][$i].'"/>';
                        }?>
                        <input id="pageNowPagination" type="hidden" name="page_now" value="<?php echo $input_info['page_now'];?>">

                        <div id="paginationHtml">
                            <?php 
                            if ($input_info['page_now']==1){
                                echo '<p class="pagination_checked" onclick="paginationBtn()">1</p>';
                            } else {
                                echo '<p onclick="paginationBtn()">1</p>';
                            }
                            for ($i=0;$i<count($input_info['pagination_count']);$i++){
                                if ($input_info['pagination_count'][$i]==$input_info['page_now']){
                                    echo '<p class="pagination_checked" onclick="paginationBtn()">'.$input_info['pagination_count'][$i].'</p>';
                                } else {
                                    echo '<p onclick="paginationBtn()">'.$input_info['pagination_count'][$i].'</p>';
                                }
                            }
                            if ($input_info['page_now']==$input_info['page_group_total']){
                                echo '<p class="pagination_checked" onclick="paginationBtn()">'.$input_info['page_group_total'].'</p>';
                            } else {
                                echo '<p onclick="paginationBtn()">'.$input_info['page_group_total'].'</p>';
                            }?>
                        </div><!-- // paginationHtml -->
                        <script>// pagination button
                            function paginationBtn(){
                                var page_now=event.target.textContent;
                                document.getElementById('pageNowPagination').value=page_now;
                                document.getElementById('formResultPagination').submit();
                            }
                        </script>
                    </form><!-- // pagination -->
                    <div>
                        <form method="POST" action="/search/result_csv">
                            <input type="hidden" name="human_protein_reviewed" value="<?php echo $input_info['human_protein'];?>">
                            <input type="hidden" name="enzyme" value="<?php echo $input_info['enzyme'];?>">
                            <input type="hidden" name="crosslinker" value="<?php echo $input_info['crosslinker'];?>">
                            <input type="hidden" name="peptide_length_min" value="<?php echo $input_info['peptide_length_min'];?>">
                            <input type="hidden" name="peptide_length_max" value="<?php echo $input_info['peptide_length_max'];?>">
                            <input type="hidden" name="sorting" value="<?php echo $input_info['ranking'];?>">
                            <input type="hidden" name="carbamidomethyl" value="<?php echo $input_info['carbamidomethyl'];?>">
                            <input type="hidden" name="oxidation" value="<?php echo $input_info['oxidation'];?>">
                            <input type="hidden" step="0.001" min=0 max=1 name="score_min" value="<?php if (!empty($input_info['score_min'])) {echo $input_info['score_min'];}?>">
                            <input type="hidden" step="0.001" min=0 max=1 name="score_max" value="<?php if (!empty($input_info['score_max'])) {echo $input_info['score_max'];}?>">
                            <input type="hidden" name="p1_hp_peptide" value="<?php echo $input_info['p1_hp_peptide']; ?>"/>
                            <input type="hidden" name="p2_hp_peptide" value="<?php echo $input_info['p2_hp_peptide']; ?>"/>
                            <?php
                            for ($i=0;$i<count($input_info['peptidecharge']);$i++){
                                echo '<input type="hidden" name="peptidecharge[]" value="'.$input_info['peptidecharge'][$i].'"/>';
                            }
                            for ($i=0;$i<count($input_info['ioncharge']);$i++){
                                echo '<input type="hidden" name="ioncharge[]" value="'.$input_info['ioncharge'][$i].'"/>';
                            }?>
                            <button type="submit">Export Result</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>