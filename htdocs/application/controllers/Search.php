<?php
ob_start();
defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends CI_Controller {
     
    public function __construct()
    {
        parent::__construct();
        
        # connect to MySQL
        $this->load->database();
    }
	


	public function index()
	{    
       
        $data['enzyme'] = $this->db->query('select id, name from enzyme')->result();
        $data['crosslinker'] = $this->db->query('select id, name from crosslinker')->result();
        

        # intergration with search page
		$this->load->view('common/header');                
        $this->load->view('search/search', $data);         
        $this->load->view('common/footer');                
	}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    # search 결과도출 알고리즘 / result Page
    public function result()
    {
        # 1. Getting input value from front-end on search page
        $human_protein = $this->input->post('human_protein_reviewed');  
        $enzyme = $this->input->post('enzyme');
        $crosslinker = $this->input->post('crosslinker');
        $peptide_length_min = $this->input->post('peptide_length_min'); 
        $peptide_length_max = $this->input->post('peptide_length_max');
        $ranking = $this->input->post('sorting'); 
        $peptidecharge = $this->input->post('peptidecharge');
        $ioncharge = $this->input->post('ioncharge');
        $carbamidomethyl_c_static = $this->input->post('carbamidomethyl_c_static');
        $carbamidomethyl_c_variable = $this->input->post('carbamidomethyl_c_variable');
        $oxidation_m_static = $this->input->post('oxidation_m_static');
        $oxidation_m_variable = $this->input->post('oxidation_m_variable');

        $Proton = 1.00727649;
        $H2O = 18.01056468403;

        $score_min = $this->input->post('score_min');
        $score_max = $this->input->post('score_max');
        if (!empty($score_min)) {
            $score_min = $score_min * 1000;
        }
        if (!empty($score_max)) {
            $score_max = $score_max * 1000;
        }
      

        # 2. Amino Acid string of modifications 
        $carbamidomethyl_c_str = 'C';
        $oxidation_m_str = 'M';


        # 3. Mass change per modification
        $carbamidomethyl_c_mass = (double) 57.021464;
        $oxidation_m_mass = (double) 15.994915;
        

        # 4. Data Query and assign to variable using input value on search page
        $query = 'select name, string, sequenceID from human_protein_reviewed where entrynumber="'.$human_protein.'"';
        $protein1_all = $this->db->query($query)->result()[0];   


        # 5. Event of no result page
        if (empty($protein1_all)) {
            header("Location: /search/noresult?s=".$human_protein);
            die();
        }


        # 6. Event of no interaction page
        if ($protein1_all->string == "NaN") {
            header("Location: /search/nointeraction?s=".$human_protein);
            die();
        }


        $query = 'select * from enzyme where id="'.$enzyme.'"';
        $enzyme_all = $this->db->query($query)->result()[0];

        $query = 'select name, binding_site, cleavability, mass, mass_c, mass_center, mass_n  from crosslinker where id="'.$crosslinker.'"';
        $crosslinker_all = $this->db->query($query)->result()[0];


        $query = 'select * from amino_acid_mass';
        $aa_mass_all = $this->db->query($query)->result();

        //$query = 'select protein2, combined_score from protein_interaction where protein1="'.$protein1_all->string.'" order by combined_score desc limit '.$ranking;
        
        ########################## // 임시 사용
        $query = 'select protein2, combined_score from protein_interaction where protein1="'.$protein1_all->string.'"';

        $probability = $this->input->post('probability');
        if ($probability != '') {
            $query = $query.' and combined_score="'.$probability.'"';
        }
        $query = $query.' order by combined_score desc limit '.$ranking;
        ########################## // 임시 사용
  
        $protein2_interaction_all = $this->db->query($query)->result();

        # 7. Convert Array to String
        $protein2_string = '';     
        for ($i=0;$i<count($protein2_interaction_all);$i++) {     
            if ($i != 0) {     
                $protein2_string .= ',';
            }
            $protein2_string .= '"';     
            $protein2_string .= $protein2_interaction_all[$i]->protein2;     
            $protein2_string .= '"';    
        }

        $query = 'select name, entrynumber, entryname, string, sequenceID from human_protein_reviewed where string IN ('.$protein2_string.') AND entrynumber not like "%-%"';
        $protein2_all_query = $this->db->query($query)->result();

        # 8. Query data rearrangement
        $protein2_all = [];     
        for ($i=0;$i<count($protein2_interaction_all);$i++) {    
            for ($j=0;$j<count($protein2_all_query);$j++) {     
                if ($protein2_interaction_all[$i]->protein2 == $protein2_all_query[$j]->string) {     
                    $arr = [];
                    $arr = [
                        'name'=>$protein2_all_query[$j]->name,
                        'entrynumber'=>$protein2_all_query[$j]->entrynumber,
                        'entryname'=>$protein2_all_query[$j]->entryname,
                        'string'=>$protein2_all_query[$j]->string,
                        'sequenceID'=>$protein2_all_query[$j]->sequenceID,
                        'combined_score'=>$protein2_interaction_all[$i]->combined_score
                    ];
                    array_push($protein2_all, $arr);
                }
            }
        }


        # 9. Data assign to export to front-end
        $interaction_info = [];     
        for ($i=0;$i<count($protein2_all);$i++) {     

            $chk_remove1 = 'N';
            if (!empty($score_min)) {
                if ($protein2_all[$i]['combined_score'] < $score_min) {
                    $chk_remove1 = 'Y';
                }
            } 
            if (!empty($score_max)) {
                if ($protein2_all[$i]['combined_score'] >= $score_max) {
                    $chk_remove1 = 'Y';
                }
            }
                
            if ($chk_remove1 == 'N') {
                for ($j=0;$j<count($protein2_interaction_all);$j++) {    
                    if ($protein2_all[$i]['string'] == $protein2_interaction_all[$j]->protein2) {     
                        array_push($interaction_info, [
                            'name'=>$protein2_all[$i]['name'],     
                            'score'=>$protein2_interaction_all[$j]->combined_score    
                        ]);
                    }
                }
            }
        }
        $data['interaction_info'] = $interaction_info;     


        # 10. Calculate total mass of reviewed human protein
        $sequenceID_sum = (double) 0;     
        for ($i=0; $i<strlen($protein1_all->sequenceID); $i++) {     
            for ($j=0; $j<count($aa_mass_all); $j++) {    
                if ($protein1_all->sequenceID[$i] == $aa_mass_all[$j]->slc) {    
                    $sequenceID_sum = $sequenceID_sum + (double) $aa_mass_all[$j]->monoisotopic;     
                }
            }
        }
        $protein1_all->sequenceID_sum = $sequenceID_sum;    
        unset($sequenceID_sum);    


        # 11. Digest reviewed human protein to peptide fragment using Enzyme
        $sequenceID_arr = seq_digestion($protein1_all->sequenceID, $enzyme_all->cleavage_site, $enzyme_all->exception);     
        $sequenceID_arr_unset = [];     
        for ($i=0; $i<count($sequenceID_arr); $i++) {  
            if (strlen($sequenceID_arr[$i]['peptide']) >= $peptide_length_min && strlen($sequenceID_arr[$i]['peptide']) <= $peptide_length_max) {    
                array_push($sequenceID_arr_unset, ['peptide'=>$sequenceID_arr[$i]['peptide']]);   
            }
        }
        $sequenceID_arr = $sequenceID_arr_unset;     
        unset($sequenceID_arr_unset);     

############################################## 2022-12-17 질량값 계산 부분 ############################################## 


        # 12. Calculate mass of peptide
        for ($i=0; $i<count($sequenceID_arr); $i++) {    
            $peptide_mass = (double)0;     
            for ($j=0; $j<strlen($sequenceID_arr[$i]['peptide']); $j++) {  
                for ($k=0; $k<count($aa_mass_all); $k++) {    

                    if ($sequenceID_arr[$i]['peptide'][$j] == $aa_mass_all[$k]->slc) {    
                        $peptide_mass = $peptide_mass + (double)$aa_mass_all[$k]->monoisotopic;    
                        
                        if ($j == strlen($sequenceID_arr[$i]['peptide']) - 1) {
                            $peptide_mass = $peptide_mass;   ###############################################
                        }
                    }
                }
                $sequenceID_arr[$i]['peptide_mass'] = $peptide_mass;
            }


            # 12-1. Make a ion fragment (b, y ion) from peptide and calculate mass of ion fragment
            $peptide_frag = [];     
            for ($j=0; $j<strlen($sequenceID_arr[$i]['peptide']); $j++) {    
                if ($j > 0 && $j < strlen($sequenceID_arr[$i]['peptide'])) {     
                    $ion = [];     
                    $b_ion = "";     
                    $y_ion = "";     
                    $b_ion = substr($sequenceID_arr[$i]['peptide'], 0, $j);     
                    $y_ion = substr($sequenceID_arr[$i]['peptide'], $j);     
                    array_push($ion, $b_ion);     
                    array_push($ion, $y_ion);    

                    $mass = [];    
                    $mass_b = (double) 0;    
                    for ($k=0; $k<strlen($b_ion); $k++) {     
                        for ($l=0; $l<count($aa_mass_all); $l++) {     
                            if ($b_ion[$k] == $aa_mass_all[$l]->slc) {     
                                $mass_b = $mass_b + (double) $aa_mass_all[$l]->monoisotopic;    
                            }
                        }
                    }
                    array_push($mass, $mass_b);     

                    $mass_y = 0;     

                    for ($k=0; $k<strlen($y_ion); $k++) {     
                        for ($l=0; $l<count($aa_mass_all); $l++) {    
                            if ($y_ion[$k] == $aa_mass_all[$l]->slc) {   
                                $mass_y = $mass_y + (double) $aa_mass_all[$l]->monoisotopic;   
                            }
                        }
                    }
                    array_push($mass, $mass_y);     
                    array_push($peptide_frag, ['ion'=>$ion, 'ion_mass'=>$mass]);     
                }   
            }
            $sequenceID_arr[$i]['peptide_frag'] = $peptide_frag;    
        }
        $protein1_all->sequenceID_arr = $sequenceID_arr;    


        # 13. Calculate total mass of reviewed human protein
        for ($i=0; $i<count($protein2_all); $i++) {   
            $sequenceID_sum = (double) 0;     
            for ($j=0; $j<strlen($protein2_all[$i]['sequenceID']); $j++) {     
                for ($k=0; $k<count($aa_mass_all); $k ++) {    
                    if ($protein2_all[$i]['sequenceID'][$j] == $aa_mass_all[$k]->slc) {     
                        $sequenceID_sum = $sequenceID_sum + (double) $aa_mass_all[$k]->monoisotopic;    
                    }
                }
            }
            $protein2_all[$i]['sequenceID_sum'] = $sequenceID_sum;     


            # 13-1. Digest reviewed human protein to peptide fragment using Enzyme
            $sequenceID_arr = seq_digestion($protein2_all[$i]['sequenceID'], $enzyme_all->cleavage_site, $enzyme_all->exception);     
            $sequenceID_arr_unset = [];     
            for ($j=0;$j<count($sequenceID_arr);$j++) {     
                if (strlen($sequenceID_arr[$j]['peptide']) >= $peptide_length_min && strlen($sequenceID_arr[$j]['peptide']) <= $peptide_length_max) {     
                    array_push($sequenceID_arr_unset, ['peptide'=>$sequenceID_arr[$j]['peptide']]);    
                }
            }
            $sequenceID_arr = $sequenceID_arr_unset;     
            unset($sequencdID_arr_unset);     


            # 13-2. Calculate mass of peptide 
            for ($j=0;$j<count($sequenceID_arr);$j++) {    
                $peptide_mass = (double)0;     
                for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++) {     
                    for ($l=0;$l<count($aa_mass_all);$l++) {    
                        if ($sequenceID_arr[$j]['peptide'][$k] == $aa_mass_all[$l]->slc) {     
                            $peptide_mass = $peptide_mass + (double)$aa_mass_all[$l]->monoisotopic;  
                        }
                    }
                }
                $sequenceID_arr[$j]['peptide_mass'] = $peptide_mass;     


                # 13-3. Make an ion fragment (b, y ion) from peptide and calculate mass of ion fragment
                $peptide_frag = [];     
                for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++) {     
                    if ($k > 0 && $k < strlen($sequenceID_arr[$j]['peptide'])) {     
                        $ion = [];     
                        $b_ion = '';    
                        $y_ion = '';    
                        $b_ion = substr($sequenceID_arr[$j]['peptide'], 0, $k);   
                        $y_ion = substr($sequenceID_arr[$j]['peptide'], $k);    

                        array_push($ion, $b_ion);     
                        array_push($ion, $y_ion);    

                        $mass = [];     
                        $mass_b = (double)0;     
                        for ($l=0;$l<strlen($b_ion);$l++) {     
                            for ($m=0;$m<count($aa_mass_all);$m++) {    
                                if ($b_ion[$l] == $aa_mass_all[$m]->slc) {    
                                    $mass_b = $mass_b + (double)$aa_mass_all[$m]->monoisotopic;    
                                }
                            }
                        }
                        array_push($mass, $mass_b);     

                        $mass_y = 0;     
                        for ($l=0;$l<strlen($y_ion);$l++) {    
                            for ($m=0;$m<count($aa_mass_all);$m++) {    
                                if ($y_ion[$l] == $aa_mass_all[$m]->slc) {   
                                    $mass_y = $mass_y + (double) $aa_mass_all[$m]->monoisotopic;      
                                }
                            }
                        }
                        array_push($mass, $mass_y);     
                        array_push($peptide_frag, ['ion'=>$ion, 'ion_mass'=>$mass]);     
                    }
                }
                $sequenceID_arr[$j]['peptide_frag'] = $peptide_frag;     
                unset($peptide_frag);     
            }
            $protein2_all[$i]['sequenceID_arr'] = $sequenceID_arr;         
        }
############################################## // 2022-12-17 질량값 계산 부분 ############################################## 

        # 14. Convert to 2 Demension Array from multi demension Array
        $protein1_result = [];    
        for ($i=0;$i<count($protein1_all->sequenceID_arr);$i++) {    
            $protein1_peptide = $protein1_all->sequenceID_arr[$i]['peptide'];     
            $protein1_peptide_mass = $protein1_all->sequenceID_arr[$i]['peptide_mass'];    
            for ($j=0;$j<count($protein1_all->sequenceID_arr[$i]['peptide_frag']);$j++) {   
                for ($k=0;$k<count($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion']);$k++) {     
                    $protein1_ion = $protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k];    
                    $protein1_ion_mass = $protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion_mass'][$k];    
                    $protein1_ion_type = '';     
                    $leng = strlen($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k]);    
                    if ($k == 0) {   
                        $protein1_ion_type = 'b'.$leng;    
                    } else {
                        $protein1_ion_type = 'y'.$leng;
                        $protein1_ion_mass = $protein1_ion_mass;  // + $H2O
                    }

                    array_push($protein1_result, [    
                        'protein1_peptide'=>$protein1_peptide,    
                        'protein1_peptide_mass'=>$protein1_peptide_mass,    
                        'protein1_ion'=>$protein1_ion,    
                        'protein1_ion_mass'=>$protein1_ion_mass,   
                        'protein1_ion_type'=>$protein1_ion_type    
                    ]);
                }
            }
        }

        $protein2_result = [];    
        for ($i=0;$i<count($protein2_all);$i++) {    
            for ($j=0;$j<count($protein2_all[$i]['sequenceID_arr']);$j++) {     
                $protein2_peptide = $protein2_all[$i]['sequenceID_arr'][$j]['peptide'];    
                $protein2_peptide_mass = $protein2_all[$i]['sequenceID_arr'][$j]['peptide_mass'];    
                for ($k=0;$k<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag']);$k++) {    
                    for ($l=0;$l<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion']);$l++) {   
                        $protein2_ion = $protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l];    
                        $protein2_ion_mass = $protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion_mass'][$l];     
                        $protein2_ion_type = '';    
                        $leng = strlen($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l]);     
                        if ($l == 0) {    
                            $protein2_ion_type = 'b'.$leng;    
                        } else {
                            $protein2_ion_type = 'y'.$leng;  
                            $protein2_ion_mass = $protein2_ion_mass; // + $H2O  
                        }

                        array_push($protein2_result, [
                            'protein2_peptide'=>$protein2_peptide,     
                            'protein2_peptide_mass'=>$protein2_peptide_mass,    
                            'protein2_ion'=>$protein2_ion,    
                            'protein2_ion_mass'=>$protein2_ion_mass,    
                            'protein2_ion_type'=>$protein2_ion_type,
                            'combined_score'=>$protein2_all[$i]['combined_score']
                        ]);
                    }
                }
            }
        }


        # 15. Data intergration 
        $result = [];     
        for ($i=0;$i<count($protein1_result);$i++) {    
            $protein1_peptide = $protein1_result[$i]['protein1_peptide'];    
            $protein1_peptide_mass = $protein1_result[$i]['protein1_peptide_mass'];    
            $protein1_ion = $protein1_result[$i]['protein1_ion'];    
            $protein1_ion_mass = $protein1_result[$i]['protein1_ion_mass'];    
            $protein1_ion_type = $protein1_result[$i]['protein1_ion_type'];    

            for ($j=0;$j<count($protein2_result);$j++) {   
                $protein2_peptide = $protein2_result[$j]['protein2_peptide'];     
                $protein2_peptide_mass = $protein2_result[$j]['protein2_peptide_mass'];    
                $protein2_ion = $protein2_result[$j]['protein2_ion'];    
                $protein2_ion_mass = $protein2_result[$j]['protein2_ion_mass'];     
                $protein2_ion_type = $protein2_result[$j]['protein2_ion_type'];     

                array_push($result, [     
                        'protein1_peptide'=>$protein1_peptide,     
                        'protein1_peptide_mass'=>$protein1_peptide_mass,    
                        'protein1_ion'=>$protein1_ion,     
                        'protein1_ion_mass'=>$protein1_ion_mass,     
                        'protein1_ion_type'=>$protein1_ion_type,     
                        'protein2_peptide'=>$protein2_peptide,   
                        'protein2_peptide_mass'=>$protein2_peptide_mass,    
                        'protein2_ion'=>$protein2_ion,    
                        'protein2_ion_mass'=>$protein2_ion_mass,   
                        'protein2_ion_type'=>$protein2_ion_type,
                        'combined_score'=>$protein2_result[$j]['combined_score']
                    ]
                );
            }
        }


        # 16. Count the number of interactions at the peptide fragment level
        for ($i=0;$i<count($result);$i++) {    
            if ($crosslinker_all->cleavability == "Y") {    
                $case = mass_case_all(     
                    $crosslinker_all->binding_site,      
                    $result[$i]['protein1_peptide'], 
                    $result[$i]['protein2_peptide'],    
                    $result[$i]['protein1_peptide_mass'],    
                    $result[$i]['protein2_peptide_mass'],    
                    $crosslinker_all->mass_c,    
                    $crosslinker_all->mass_n,    
                );
            } else {    
                $case = mass_case_all(    
                    $crosslinker_all->binding_site,     
                    $result[$i]['protein1_peptide'],    
                    $result[$i]['protein2_peptide'],  
                    $result[$i]['protein1_peptide_mass'],    
                    $result[$i]['protein2_peptide_mass'],  
                    $crosslinker_all->mass,    
                    $crosslinker_all->mass,   
                );
            }
            $result[$i]['case'] = $case;    
        }


        # 17. Output data intergration
        $result_c = [];     
        for ($i=0;$i<count($result);$i++) {     
            for ($j=0;$j<count($result[$i]['case']);$j++) {   
                array_push($result_c, [     
                    'protein1_peptide'=>$result[$i]['protein1_peptide'],     
                    'protein1_peptide_mass'=>$result[$i]['protein1_peptide_mass'],    
                    'protein1_ion'=>$result[$i]['protein1_ion'],  
                    'protein1_ion_mass'=>$result[$i]['protein1_ion_mass'],     
                    'protein1_ion_type'=>$result[$i]['protein1_ion_type'],   
                    'protein2_peptide'=>$result[$i]['protein2_peptide'],     
                    'protein2_peptide_mass'=>$result[$i]['protein2_peptide_mass'],     
                    'protein2_ion'=>$result[$i]['protein2_ion'],    
                    'protein2_ion_mass'=>$result[$i]['protein2_ion_mass'],     
                    'protein2_ion_type'=>$result[$i]['protein2_ion_type'],    
                    'protein1_peptide_c_term_mass'=>$result[$i]['case'][$j]['protein1_c_term_mass'],     
                    'protein2_peptide_n_term_mass'=>$result[$i]['case'][$j]['protein2_n_term_mass'],     
                    'protein1_peptide_n_term_mass'=>$result[$i]['case'][$j]['protein1_n_term_mass'],    
                    'protein2_peptide_c_term_mass'=>$result[$i]['case'][$j]['protein2_c_term_mass'],
                    'combined_score'=>$result[$i]['combined_score']
                ]);
            }
        }
        $result = $result_c; 
        


        # 18. Count the number of interactions at the ion fragment level
        for ($i=0;$i<count($result);$i++) {    
            if ($crosslinker_all->cleavability == "Y") {  
                $case = mass_case_all(     
                    $crosslinker_all->binding_site,    
                    $result[$i]['protein1_ion'],   
                    $result[$i]['protein2_ion'],   
                    $result[$i]['protein1_ion_mass'],  
                    $result[$i]['protein2_ion_mass'],   
                    $crosslinker_all->mass_c,    
                    $crosslinker_all->mass_n,
                );
            } else {   
                $case = mass_case_all(   
                    $crosslinker_all->binding_site,    
                    $result[$i]['protein1_ion'],    
                    $result[$i]['protein2_ion'],  
                    $result[$i]['protein1_ion_mass'],    
                    $result[$i]['protein2_ion_mass'],      
                    $crosslinker_all->mass,   
                    $crosslinker_all->mass,    
                );
            }
            $result[$i]['case'] = $case;    
        }


        # 19. Output data intergration
        $result_c = [];    
        for ($i=0;$i<count($result);$i++) {    
            for ($j=0;$j<count($result[$i]['case']);$j++) {    
                array_push($result_c, [     
                    'protein1_peptide'=>$result[$i]['protein1_peptide'],   
                    'protein1_peptide_mass'=>$result[$i]['protein1_peptide_mass'],    
                    'protein1_ion'=>$result[$i]['protein1_ion'],     
                    'protein1_ion_mass'=>$result[$i]['protein1_ion_mass'],    
                    'protein1_ion_type'=>$result[$i]['protein1_ion_type'],   
                    'protein2_peptide'=>$result[$i]['protein2_peptide'],    
                    'protein2_peptide_mass'=>$result[$i]['protein2_peptide_mass'],    
                    'protein2_ion'=>$result[$i]['protein2_ion'],     
                    'protein2_ion_mass'=>$result[$i]['protein2_ion_mass'],    
                    'protein2_ion_type'=>$result[$i]['protein2_ion_type'],    
                    'protein1_peptide_c_term_mass'=>$result[$i]['protein1_peptide_c_term_mass'],    
                    'protein2_peptide_n_term_mass'=>$result[$i]['protein2_peptide_n_term_mass'],    
                    'protein1_peptide_n_term_mass'=>$result[$i]['protein1_peptide_n_term_mass'],     
                    'protein2_peptide_c_term_mass'=>$result[$i]['protein2_peptide_c_term_mass'],    
                    'protein1_ion_c_term_mass'=>$result[$i]['case'][$j]['protein1_c_term_mass'],   
                    'protein2_ion_n_term_mass'=>$result[$i]['case'][$j]['protein2_n_term_mass'],    
                    'protein1_ion_n_term_mass'=>$result[$i]['case'][$j]['protein1_n_term_mass'],   
                    'protein2_ion_c_term_mass'=>$result[$i]['case'][$j]['protein2_c_term_mass'],
                    'peptidecharge'=>'-',
                    'ioncharge'=>'-',
                    'combined_score'=>$result[$i]['combined_score']
                ]);
            }
        }
        $result = $result_c; 


        # 20. Data assign to export to front-end
        $result_c = [];    
        $result_id = 1;

        # Combinded score Setting
        for ($i=0;$i<count($result);$i++) {   

            $chk_remove2 = 'N';
            if (!empty($score_min)) {
                if ($result[$i]['combined_score'] < $score_min) {
                    $chk_remove2 = 'Y';
                }
            } 
            if (!empty($score_max)) {
                if ($result[$i]['combined_score'] >= $score_max) {
                    $chk_remove2 = 'Y';
                }
            }

            if ($chk_remove2 == 'N') {

                $protein1_peptide_mass = $result[$i]['protein1_peptide_mass']; 
                $protein1_ion_mass = $result[$i]['protein1_ion_mass'];     
                $protein2_peptide_mass = $result[$i]['protein2_peptide_mass'];    
                $protein2_ion_mass = $result[$i]['protein2_ion_mass'];    

                $protein1_peptide_c_term_mass = $result[$i]['protein1_peptide_c_term_mass'];    
                $protein2_peptide_n_term_mass = $result[$i]['protein2_peptide_n_term_mass'];    
                $protein1_peptide_n_term_mass = $result[$i]['protein1_peptide_n_term_mass'];    
                $protein2_peptide_c_term_mass = $result[$i]['protein2_peptide_c_term_mass'];    

                $protein1_ion_c_term_mass = $result[$i]['protein1_ion_c_term_mass'];     
                $protein2_ion_n_term_mass = $result[$i]['protein2_ion_n_term_mass'];     
                $protein1_ion_n_term_mass = $result[$i]['protein1_ion_n_term_mass'];     
                $protein2_ion_c_term_mass = $result[$i]['protein2_ion_c_term_mass'];  
                



                # 20-1. Modification : Carbamidomethyl (C) - Calculate mass of change 
                if ($carbamidomethyl_c_static == 'Y' || $carbamidomethyl_c_variable == 'Y') {
                    if (strpos($result[$i]['protein1_peptide'],  $carbamidomethyl_c_str) !== FALSE) {
                        $protein1_peptide_mass = $protein1_peptide_mass + $carbamidomethyl_c_mass;     
                        $protein1_peptide_c_term_mass = $protein1_peptide_c_term_mass + $carbamidomethyl_c_mass;    
                        $protein1_peptide_n_term_mass = $protein1_peptide_n_term_mass + $carbamidomethyl_c_mass;  
                    }
                }

                if ($carbamidomethyl_c_static == 'Y' || $carbamidomethyl_c_variable == 'Y') {
                    if (strpos($result[$i]['protein2_peptide'],  $carbamidomethyl_c_str) !== FALSE) {
                    $protein2_peptide_mass = $protein2_peptide_mass + $carbamidomethyl_c_mass;    
                    $protein2_peptide_n_term_mass = $protein2_peptide_n_term_mass + $carbamidomethyl_c_mass;    
                    $protein2_peptide_c_term_mass = $protein2_peptide_c_term_mass + $carbamidomethyl_c_mass;   
                    }
                }

                if ($carbamidomethyl_c_static == 'Y' || $carbamidomethyl_c_variable == 'Y') {
                    if (strpos($result[$i]['protein1_ion'],  $carbamidomethyl_c_str) !== FALSE) {
                    $protein1_ion_mass = $protein1_ion_mass + $carbamidomethyl_c_mass;    
                    $protein1_ion_c_term_mass = $protein1_ion_c_term_mass + $carbamidomethyl_c_mass;   
                    $protein1_ion_n_term_mass = $protein1_ion_n_term_mass + $carbamidomethyl_c_mass;    
                    }
                }           
                
                if ($carbamidomethyl_c_static == 'Y' || $carbamidomethyl_c_variable == 'Y') {
                    if (strpos($result[$i]['protein2_ion'],  $carbamidomethyl_c_str) !== FALSE) {
                    $protein2_ion_mass = $protein2_ion_mass + $carbamidomethyl_c_mass;     
                    $protein2_ion_n_term_mass = $protein2_ion_n_term_mass + $carbamidomethyl_c_mass;    
                    $protein2_ion_c_term_mass = $protein2_ion_c_term_mass + $carbamidomethyl_c_mass;  
                    }
                }

                # 20-2. Modification : Oxidation (M)) - Calculate mass of change 

                if ($oxidation_m_static == 'Y' || $oxidation_m_variable == 'Y') {
                    if (strpos($result[$i]['protein1_peptide'], $oxidation_m_str) !== FALSE) {
                    $protein1_peptide_mass = $protein1_peptide_mass + $oxidation_m_mass;    
                    $protein1_peptide_c_term_mass = $protein1_peptide_c_term_mass + $oxidation_m_mass;    
                    $protein1_peptide_n_term_mass = $protein1_peptide_n_term_mass + $oxidation_m_mass;      
                    }
                }

                if ($oxidation_m_static == 'Y' || $oxidation_m_variable == 'Y') {
                    if (strpos($result[$i]['protein2_peptide'], $oxidation_m_str) !== FALSE) {
                    $protein2_peptide_mass = $protein2_peptide_mass + $oxidation_m_mass;   
                    $protein2_peptide_n_term_mass = $protein2_peptide_n_term_mass + $oxidation_m_mass;   
                    $protein2_peptide_c_term_mass = $protein2_peptide_c_term_mass + $oxidation_m_mass;      
                    }
                }

                if ($oxidation_m_static == 'Y' || $oxidation_m_variable == 'Y') {
                    if (strpos($result[$i]['protein1_ion'], $oxidation_m_str) !== FALSE) {
                    $protein1_ion_mass = $protein1_ion_mass + $oxidation_m_mass;    
                    $protein1_ion_c_term_mass = $protein1_ion_c_term_mass + $oxidation_m_mass;   
                    $protein1_ion_n_term_mass = $protein1_ion_n_term_mass + $oxidation_m_mass;         
                    }
                }
              
                if ($oxidation_m_static == 'Y' || $oxidation_m_variable == 'Y') {
                    if (strpos($result[$i]['protein2_ion'], $oxidation_m_str) !== FALSE) {
                    $protein2_ion_mass = $protein2_ion_mass + $oxidation_m_mass;   
                    $protein2_ion_n_term_mass = $protein2_ion_n_term_mass + $oxidation_m_mass;    
                    $protein2_ion_c_term_mass = $protein2_ion_c_term_mass + $oxidation_m_mass;          
                    }
                }

                # 20-3. Peptide, Ion Charge : Calculate mass of change
                for ($j=0;$j<count($peptidecharge);$j++) {
                    for ($k=0;$k<count($ioncharge);$k++) {

                        # 20-4. Count Static, Variable of Modifications 
                        if ($carbamidomethyl_c_variable == 'Y' || $oxidation_m_variable == 'Y') {    
                            if ( 
                                strpos($result[$i]['protein1_peptide'], $carbamidomethyl_c_str) !== FALSE ||    
                                strpos($result[$i]['protein1_ion'], $carbamidomethyl_c_str) !== FALSE ||    
                                strpos($result[$i]['protein2_peptide'], $carbamidomethyl_c_str) !== FALSE ||    
                                strpos($result[$i]['protein2_ion'], $carbamidomethyl_c_str) !== FALSE ||    
                                strpos($result[$i]['protein1_peptide'], $oxidation_m_str) !== FALSE ||    
                                strpos($result[$i]['protein1_ion'], $oxidation_m_str) !== FALSE ||    
                                strpos($result[$i]['protein2_peptide'], $oxidation_m_str) !== FALSE ||   
                                strpos($result[$i]['protein2_ion'], $oxidation_m_str) !== FALSE    
                            ) { 
                                array_push($result_c, [
                                    'block'=>'A',  
                                    'id'=>$result_id,
                                    'combined_score'=>$result[$i]['combined_score'] / 1000,
                                    'protein1_peptide'=>$result[$i]['protein1_peptide'],
                                    'crosslinker'=>$crosslinker_all->name,
                                    'protein2_peptide'=>$result[$i]['protein2_peptide'],
                                    'peptidecharge'=>$peptidecharge[$j],

                                    'protein1_peptide_c_term_mass'=>round($Proton + ($result[$i]['protein1_peptide_c_term_mass'] + $H2O) / $peptidecharge[$j], 4),                                   
                                    'center_mass_peptide_1'=>round($crosslinker_all->mass_center / $peptidecharge[$j], 4),
                                    'protein2_peptide_n_term_mass'=>round($Proton + ($result[$i]['protein2_peptide_n_term_mass'] + $H2O) / $peptidecharge[$j], 4),
                                    'protein1_peptide_n_term_mass'=>round($Proton + ($result[$i]['protein1_peptide_n_term_mass'] + $H2O) / $peptidecharge[$j], 4),
                                    'center_mass_peptide_2'=>round($crosslinker_all->mass_center / $peptidecharge[$j], 4),
                                    'protein2_peptide_c_term_mass'=>round($Proton + ($result[$i]['protein2_peptide_c_term_mass'] + $H2O) / $peptidecharge[$j], 4),

                                    'protein1_ion_type'=>$result[$i]['protein1_ion_type'],  
                                    'protein1_ion'=>$result[$i]['protein1_ion'],   
                                    'protein2_ion_type'=>$result[$i]['protein2_ion_type'],  
                                    'protein2_ion'=>$result[$i]['protein2_ion'], 
                                    'ioncharge'=>$ioncharge[$k],

                                    'protein1_ion_c_term_mass'=>round($Proton + ($result[$i]['protein1_ion_c_term_mass'] + $H2O)/ $ioncharge[$k], 4),                                      
                                    'center_mass_ion_1'=>round($crosslinker_all->mass_center / $ioncharge[$k], 4),
                                    'protein2_ion_n_term_mass'=>round($Proton + ($result[$i]['protein2_ion_n_term_mass'] + $H2O) / $ioncharge[$k], 4),   
                                    'protein1_ion_n_term_mass'=>round($Proton + ($result[$i]['protein1_ion_n_term_mass'] + $H2O) / $ioncharge[$k], 4),  
                                    'center_mass_ion_2'=>round($crosslinker_all->mass_center / $ioncharge[$k], 4),
                                    'protein2_ion_c_term_mass'=>round($Proton + ($result[$i]['protein2_ion_c_term_mass'] + $H2O) / $ioncharge[$k], 4)
                                ]);

                                $result_id = $result_id + 1;
                            }   
                        }

                        # 20-5. Calculate mass value considering peptide / ion charge
                        // $protein1_peptide_mass2 = $protein1_peptide_mass / $peptidecharge[$j];
                        // $protein2_peptide_mass2 = $protein2_peptide_mass / $peptidecharge[$j];     


                        # Originaal code
                        // $protein1_peptide_c_term_mass2 = $Proton + (($protein1_peptide_c_term_mass + $H2O) / $peptidecharge[$j]);    
                        // $protein2_peptide_n_term_mass2 = $Proton + (($protein2_peptide_n_term_mass + $H2O) / $peptidecharge[$j]);    
                        // $protein1_peptide_n_term_mass2 = $Proton + (($protein1_peptide_n_term_mass + $H2O) / $peptidecharge[$j]);    
                        // $protein2_peptide_c_term_mass2 = $Proton + (($protein2_peptide_c_term_mass + $H2O) / $peptidecharge[$j]);     


                        $protein1_peptide_c_term_mass2 = $protein1_peptide_c_term_mass;    
                        $protein2_peptide_n_term_mass2 = $protein2_peptide_n_term_mass;    
                        $protein1_peptide_n_term_mass2 = $protein1_peptide_n_term_mass;    
                        $protein2_peptide_c_term_mass2 = $protein2_peptide_c_term_mass;    


                        // $protein1_ion_mass2 = $protein1_ion_mass / $ioncharge[$k];  
                        // $protein2_ion_mass2 = $protein2_ion_mass / $ioncharge[$k];     

                        # Original code
                        // $protein1_ion_c_term_mass2 = $Proton + ($protein1_ion_c_term_mass + $H2O) / $ioncharge[$k];    
                        // $protein2_ion_n_term_mass2 = $Proton + ($protein2_ion_n_term_mass + $H2O)/ $ioncharge[$k];    
                        // $protein1_ion_n_term_mass2 = $Proton + ($protein1_ion_n_term_mass + $H2O) / $ioncharge[$k];    
                        // $protein2_ion_c_term_mass2 = $Proton + ($protein2_ion_c_term_mass + $H2O) / $ioncharge[$k];    
                        
                        $protein1_ion_c_term_mass2 = $protein1_ion_c_term_mass;    
                        $protein2_ion_n_term_mass2 = $protein2_ion_n_term_mass;    
                        $protein1_ion_n_term_mass2 = $protein1_ion_n_term_mass;    
                        $protein2_ion_c_term_mass2 = $protein2_ion_c_term_mass; 

                        
                        array_push($result_c, [
                            'block'=>'B',     
                            'id'=>$result_id,
                            'combined_score'=>$result[$i]['combined_score'] / 1000,
                            'protein1_peptide'=>$result[$i]['protein1_peptide'],    
                            'crosslinker'=>$crosslinker_all->name,
                            'protein2_peptide'=>$result[$i]['protein2_peptide'], 
                            'peptidecharge'=>$peptidecharge[$j], 


                            'protein1_peptide_c_term_mass'=>round($Proton + ($protein1_peptide_c_term_mass2 + $H2O) / $peptidecharge[$j], 4),   
                            'center_mass_peptide_1'=>round($crosslinker_all->mass_center / $peptidecharge[$j], 4),
                            'protein2_peptide_n_term_mass'=>round($Proton + ($protein2_peptide_n_term_mass2 + $H2O) / $peptidecharge[$j], 4),   
                            'protein1_peptide_n_term_mass'=>round($Proton + ($protein1_peptide_n_term_mass2 + $H2O) / $peptidecharge[$j], 4),  
                            'center_mass_peptide_2'=>round($crosslinker_all->mass_center / $peptidecharge[$j], 4),
                            'protein2_peptide_c_term_mass'=>round($Proton + ($protein2_peptide_c_term_mass2 + $H2O) / $peptidecharge[$j], 4),   

                            'protein1_ion_type'=>$result[$i]['protein1_ion_type'],  
                            'protein1_ion'=>$result[$i]['protein1_ion'],    
                            'protein2_ion_type'=>$result[$i]['protein2_ion_type'],  
                            'protein2_ion'=>$result[$i]['protein2_ion'],     
                            'ioncharge'=>$ioncharge[$k],
 
                            'protein1_ion_c_term_mass'=>round($Proton + ($protein1_ion_c_term_mass2 + $H2O) / $ioncharge[$k], 4),  
                            'center_mass_ion_1'=>round($crosslinker_all->mass_center / $ioncharge[$k], 4),
                            'protein2_ion_n_term_mass'=>round($Proton + ($protein2_ion_n_term_mass2 + $H2O) / $ioncharge[$k], 4),   
                            'protein1_ion_n_term_mass'=>round($Proton + ($protein1_ion_n_term_mass2 + $H2O) / $ioncharge[$k], 4), 
                            'center_mass_ion_2'=>round($crosslinker_all->mass_center / $ioncharge[$k], 4),
                            'protein2_ion_c_term_mass'=>round($Proton + ($protein2_ion_c_term_mass2 + $H2O) / $ioncharge[$k], 4),
                        ]);
                        $result_id = $result_id + 1;  
                    }
                } 
            }
        }

        $result = $result_c;   
        unset($result_c);


        # 21. Pagination

        $page_list = 100;
        $page_group = 20;
        $page_now = $this->input->post('page_now');
        $page_total = count($result);
        $page_group_total = ceil($page_total / $page_list);

        if (empty($page_now)) {
            $page_now = 1;
        }
        if ($page_now == 1) {
            $page_list_start = 0;
        } else {
            $page_list_start = ($page_now - 1) * $page_list;
        }
        $result = array_slice($result, $page_list_start, $page_list);


        # 22. pagination html

        $pagination_count = [];

        $page_group_start = $page_now - ($page_group / 2);
        $page_group_end = $page_now + ($page_group / 2);

        for ($i=$page_group_start;$i<$page_group_end;$i++) {

            if ($i > 1) {
                if ($i < $page_group_total) {
                    array_push($pagination_count, $i);
                }
            } 

        }


        # 23. Keep input infomation
        if (!empty($score_min)) {
            $score_min = $score_min / 1000;
        }
        if (!empty($score_max)) {
            $score_max = $score_max / 1000;
        }
        $input_info = [
            'score_min'=>$score_min,
            'score_max'=>$score_max,
            'human_protein'=>$human_protein,
            'enzyme'=>$enzyme,
            'crosslinker'=>$crosslinker,
            'peptide_length_min'=>$peptide_length_min,
            'peptide_length_max'=>$peptide_length_max,
            'ranking'=>$ranking,
            'peptidecharge'=>$peptidecharge,
            'ioncharge'=>$ioncharge,
            'carbamidomethyl_c_static'=>$carbamidomethyl_c_static,
            'carbamidomethyl_c_variable'=>$carbamidomethyl_c_variable,
            'oxidation_m_static'=>$oxidation_m_static,
            'oxidation_m_variable'=>$oxidation_m_variable,
            'page_now'=>$page_now,
            'page_group_total'=>$page_group_total,
            'pagination_count'=>$pagination_count
        ];
        $data['input_info'] = (array) $input_info;


        # 24. Assign final result data to export to result page of front-end 

        $data['search_protein'] = $protein1_all->name;   
        $data['crosslinker'] = $crosslinker_all;    
        $data['ioncharge'] = $ioncharge;    
        $data['result'] = $result;


        # 25. intergration with result page

		$this->load->view('common/header');                    
        $this->load->view('search/result', $data);           
        $this->load->view('common/footer');                
    }


    # intergration with no-result page

    public function noresult()
    {
        $this->load->view('common/header');                   
        $this->load->view('search/noresult');             
        $this->load->view('common/footer');                  
    }


    # intergration with no-interaction page
    public function nointeraction()
    {
        $this->load->view('common/header');                   
        $this->load->view('search/nointeraction');             
        $this->load->view('common/footer');                   
    }



#####################################################################

# Code repeat for Export Result Data

    public function result_csv()
    {
        # 1~20-5번 까지의 과정을 그대로 복사하여 설정 유지
        
       # 1. Getting input value from front-end on search page
       $human_protein = $this->input->post('human_protein_reviewed');  
       $enzyme = $this->input->post('enzyme');
       $crosslinker = $this->input->post('crosslinker');
       $peptide_length_min = $this->input->post('peptide_length_min'); 
       $peptide_length_max = $this->input->post('peptide_length_max');
       $ranking = $this->input->post('sorting'); 
       $peptidecharge = $this->input->post('peptidecharge');
       $ioncharge = $this->input->post('ioncharge');
       $carbamidomethyl_c_static = $this->input->post('carbamidomethyl_c_static');
       $carbamidomethyl_c_variable = $this->input->post('carbamidomethyl_c_variable');
       $oxidation_m_static = $this->input->post('oxidation_m_static');
       $oxidation_m_variable = $this->input->post('oxidation_m_variable');

       $Proton = 1.00727649;
       $H2O = 18.01056468403;

       $score_min = $this->input->post('score_min');
       $score_max = $this->input->post('score_max');
       if (!empty($score_min)) {
           $score_min = $score_min * 1000;
       }
       if (!empty($score_max)) {
           $score_max = $score_max * 1000;
       }
     

       # 2. Amino Acid string of modifications 
       $carbamidomethyl_c_str = 'C';
       $oxidation_m_str = 'M';


       # 3. Mass change per modification
       $carbamidomethyl_c_mass = (double) 57.021464;
       $oxidation_m_mass = (double) 15.994915;
       

       # 4. Data Query and assign to variable using input value on search page
       $query = 'select name, string, sequenceID from human_protein_reviewed where entrynumber="'.$human_protein.'"';
       $protein1_all = $this->db->query($query)->result()[0];   


       # 5. Event of no result page
       if (empty($protein1_all)) {
           header("Location: /search/noresult?s=".$human_protein);
           die();
       }


       # 6. Event of no interaction page
       if ($protein1_all->string == "NaN") {
           header("Location: /search/nointeraction?s=".$human_protein);
           die();
       }


       $query = 'select * from enzyme where id="'.$enzyme.'"';
       $enzyme_all = $this->db->query($query)->result()[0];

       $query = 'select name, binding_site, cleavability, mass, mass_c, mass_center, mass_n  from crosslinker where id="'.$crosslinker.'"';
       $crosslinker_all = $this->db->query($query)->result()[0];


       $query = 'select * from amino_acid_mass';
       $aa_mass_all = $this->db->query($query)->result();

       //$query = 'select protein2, combined_score from protein_interaction where protein1="'.$protein1_all->string.'" order by combined_score desc limit '.$ranking;
       
       ########################## // 임시 사용
       $query = 'select protein2, combined_score from protein_interaction where protein1="'.$protein1_all->string.'"';

       $probability = $this->input->post('probability');
       if ($probability != '') {
           $query = $query.' and combined_score="'.$probability.'"';
       }
       $query = $query.' order by combined_score desc limit '.$ranking;
       ########################## // 임시 사용
 
       $protein2_interaction_all = $this->db->query($query)->result();

       # 7. Convert Array to String
       $protein2_string = '';     
       for ($i=0;$i<count($protein2_interaction_all);$i++) {     
           if ($i != 0) {     
               $protein2_string .= ',';
           }
           $protein2_string .= '"';     
           $protein2_string .= $protein2_interaction_all[$i]->protein2;     
           $protein2_string .= '"';    
       }

       $query = 'select name, entrynumber, entryname, string, sequenceID from human_protein_reviewed where string IN ('.$protein2_string.') AND entrynumber not like "%-%"';
       $protein2_all_query = $this->db->query($query)->result();

       # 8. Query data rearrangement
       $protein2_all = [];     
       for ($i=0;$i<count($protein2_interaction_all);$i++) {    
           for ($j=0;$j<count($protein2_all_query);$j++) {     
               if ($protein2_interaction_all[$i]->protein2 == $protein2_all_query[$j]->string) {     
                   $arr = [];
                   $arr = [
                       'name'=>$protein2_all_query[$j]->name,
                       'entrynumber'=>$protein2_all_query[$j]->entrynumber,
                       'entryname'=>$protein2_all_query[$j]->entryname,
                       'string'=>$protein2_all_query[$j]->string,
                       'sequenceID'=>$protein2_all_query[$j]->sequenceID,
                       'combined_score'=>$protein2_interaction_all[$i]->combined_score
                   ];
                   array_push($protein2_all, $arr);
               }
           }
       }


       # 9. Data assign to export to front-end
       $interaction_info = [];     
       for ($i=0;$i<count($protein2_all);$i++) {     

           $chk_remove1 = 'N';
           if (!empty($score_min)) {
               if ($protein2_all[$i]['combined_score'] < $score_min) {
                   $chk_remove1 = 'Y';
               }
           } 
           if (!empty($score_max)) {
               if ($protein2_all[$i]['combined_score'] >= $score_max) {
                   $chk_remove1 = 'Y';
               }
           }
               
           if ($chk_remove1 == 'N') {
               for ($j=0;$j<count($protein2_interaction_all);$j++) {    
                   if ($protein2_all[$i]['string'] == $protein2_interaction_all[$j]->protein2) {     
                       array_push($interaction_info, [
                           'name'=>$protein2_all[$i]['name'],     
                           'score'=>$protein2_interaction_all[$j]->combined_score    
                       ]);
                   }
               }
           }
       }
       $data['interaction_info'] = $interaction_info;     


       # 10. Calculate total mass of reviewed human protein
       $sequenceID_sum = (double) 0;     
       for ($i=0; $i<strlen($protein1_all->sequenceID); $i++) {     
           for ($j=0; $j<count($aa_mass_all); $j++) {    
               if ($protein1_all->sequenceID[$i] == $aa_mass_all[$j]->slc) {    
                   $sequenceID_sum = $sequenceID_sum + (double) $aa_mass_all[$j]->monoisotopic;     
               }
           }
       }
       $protein1_all->sequenceID_sum = $sequenceID_sum;    
       unset($sequenceID_sum);    


       # 11. Digest reviewed human protein to peptide fragment using Enzyme
       $sequenceID_arr = seq_digestion($protein1_all->sequenceID, $enzyme_all->cleavage_site, $enzyme_all->exception);     
       $sequenceID_arr_unset = [];     
       for ($i=0; $i<count($sequenceID_arr); $i++) {  
           if (strlen($sequenceID_arr[$i]['peptide']) >= $peptide_length_min && strlen($sequenceID_arr[$i]['peptide']) <= $peptide_length_max) {    
               array_push($sequenceID_arr_unset, ['peptide'=>$sequenceID_arr[$i]['peptide']]);   
           }
       }
       $sequenceID_arr = $sequenceID_arr_unset;     
       unset($sequenceID_arr_unset);     

############################################## 2022-12-17 질량값 계산 부분 ############################################## 


       # 12. Calculate mass of peptide
       for ($i=0; $i<count($sequenceID_arr); $i++) {    
           $peptide_mass = (double)0;     
           for ($j=0; $j<strlen($sequenceID_arr[$i]['peptide']); $j++) {  
               for ($k=0; $k<count($aa_mass_all); $k++) {    

                   if ($sequenceID_arr[$i]['peptide'][$j] == $aa_mass_all[$k]->slc) {    
                       $peptide_mass = $peptide_mass + (double)$aa_mass_all[$k]->monoisotopic;    
                       
                       if ($j == strlen($sequenceID_arr[$i]['peptide']) - 1) {
                           $peptide_mass = $peptide_mass;   ###############################################
                       }
                   }
               }
               $sequenceID_arr[$i]['peptide_mass'] = $peptide_mass;
           }


           # 12-1. Make a ion fragment (b, y ion) from peptide and calculate mass of ion fragment
           $peptide_frag = [];     
           for ($j=0; $j<strlen($sequenceID_arr[$i]['peptide']); $j++) {    
               if ($j > 0 && $j < strlen($sequenceID_arr[$i]['peptide'])) {     
                   $ion = [];     
                   $b_ion = "";     
                   $y_ion = "";     
                   $b_ion = substr($sequenceID_arr[$i]['peptide'], 0, $j);     
                   $y_ion = substr($sequenceID_arr[$i]['peptide'], $j);     
                   array_push($ion, $b_ion);     
                   array_push($ion, $y_ion);    

                   $mass = [];    
                   $mass_b = (double) 0;    
                   for ($k=0; $k<strlen($b_ion); $k++) {     
                       for ($l=0; $l<count($aa_mass_all); $l++) {     
                           if ($b_ion[$k] == $aa_mass_all[$l]->slc) {     
                               $mass_b = $mass_b + (double) $aa_mass_all[$l]->monoisotopic;    
                           }
                       }
                   }
                   array_push($mass, $mass_b);     

                   $mass_y = 0;     

                   for ($k=0; $k<strlen($y_ion); $k++) {     
                       for ($l=0; $l<count($aa_mass_all); $l++) {    
                           if ($y_ion[$k] == $aa_mass_all[$l]->slc) {   
                               $mass_y = $mass_y + (double) $aa_mass_all[$l]->monoisotopic;   
                           }
                       }
                   }
                   array_push($mass, $mass_y);     
                   array_push($peptide_frag, ['ion'=>$ion, 'ion_mass'=>$mass]);     
               }   
           }
           $sequenceID_arr[$i]['peptide_frag'] = $peptide_frag;    
       }
       $protein1_all->sequenceID_arr = $sequenceID_arr;    


       # 13. Calculate total mass of reviewed human protein
       for ($i=0; $i<count($protein2_all); $i++) {   
           $sequenceID_sum = (double) 0;     
           for ($j=0; $j<strlen($protein2_all[$i]['sequenceID']); $j++) {     
               for ($k=0; $k<count($aa_mass_all); $k ++) {    
                   if ($protein2_all[$i]['sequenceID'][$j] == $aa_mass_all[$k]->slc) {     
                       $sequenceID_sum = $sequenceID_sum + (double) $aa_mass_all[$k]->monoisotopic;    
                   }
               }
           }
           $protein2_all[$i]['sequenceID_sum'] = $sequenceID_sum;     


           # 13-1. Digest reviewed human protein to peptide fragment using Enzyme
           $sequenceID_arr = seq_digestion($protein2_all[$i]['sequenceID'], $enzyme_all->cleavage_site, $enzyme_all->exception);     
           $sequenceID_arr_unset = [];     
           for ($j=0;$j<count($sequenceID_arr);$j++) {     
               if (strlen($sequenceID_arr[$j]['peptide']) >= $peptide_length_min && strlen($sequenceID_arr[$j]['peptide']) <= $peptide_length_max) {     
                   array_push($sequenceID_arr_unset, ['peptide'=>$sequenceID_arr[$j]['peptide']]);    
               }
           }
           $sequenceID_arr = $sequenceID_arr_unset;     
           unset($sequencdID_arr_unset);     


           # 13-2. Calculate mass of peptide 
           for ($j=0;$j<count($sequenceID_arr);$j++) {    
               $peptide_mass = (double)0;     
               for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++) {     
                   for ($l=0;$l<count($aa_mass_all);$l++) {    
                       if ($sequenceID_arr[$j]['peptide'][$k] == $aa_mass_all[$l]->slc) {     
                           $peptide_mass = $peptide_mass + (double)$aa_mass_all[$l]->monoisotopic;  
                       }
                   }
               }
               $sequenceID_arr[$j]['peptide_mass'] = $peptide_mass;     


               # 13-3. Make an ion fragment (b, y ion) from peptide and calculate mass of ion fragment
               $peptide_frag = [];     
               for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++) {     
                   if ($k > 0 && $k < strlen($sequenceID_arr[$j]['peptide'])) {     
                       $ion = [];     
                       $b_ion = '';    
                       $y_ion = '';    
                       $b_ion = substr($sequenceID_arr[$j]['peptide'], 0, $k);   
                       $y_ion = substr($sequenceID_arr[$j]['peptide'], $k);    

                       array_push($ion, $b_ion);     
                       array_push($ion, $y_ion);    

                       $mass = [];     
                       $mass_b = (double)0;     
                       for ($l=0;$l<strlen($b_ion);$l++) {     
                           for ($m=0;$m<count($aa_mass_all);$m++) {    
                               if ($b_ion[$l] == $aa_mass_all[$m]->slc) {    
                                   $mass_b = $mass_b + (double)$aa_mass_all[$m]->monoisotopic;    
                               }
                           }
                       }
                       array_push($mass, $mass_b);     

                       $mass_y = 0;     
                       for ($l=0;$l<strlen($y_ion);$l++) {    
                           for ($m=0;$m<count($aa_mass_all);$m++) {    
                               if ($y_ion[$l] == $aa_mass_all[$m]->slc) {   
                                   $mass_y = $mass_y + (double) $aa_mass_all[$m]->monoisotopic;      
                               }
                           }
                       }
                       array_push($mass, $mass_y);     
                       array_push($peptide_frag, ['ion'=>$ion, 'ion_mass'=>$mass]);     
                   }
               }
               $sequenceID_arr[$j]['peptide_frag'] = $peptide_frag;     
               unset($peptide_frag);     
           }
           $protein2_all[$i]['sequenceID_arr'] = $sequenceID_arr;         
       }
############################################## // 2022-12-17 질량값 계산 부분 ############################################## 

       # 14. Convert to 2 Demension Array from multi demension Array
       $protein1_result = [];    
       for ($i=0;$i<count($protein1_all->sequenceID_arr);$i++) {    
           $protein1_peptide = $protein1_all->sequenceID_arr[$i]['peptide'];     
           $protein1_peptide_mass = $protein1_all->sequenceID_arr[$i]['peptide_mass'];    
           for ($j=0;$j<count($protein1_all->sequenceID_arr[$i]['peptide_frag']);$j++) {   
               for ($k=0;$k<count($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion']);$k++) {     
                   $protein1_ion = $protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k];    
                   $protein1_ion_mass = $protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion_mass'][$k];    
                   $protein1_ion_type = '';     
                   $leng = strlen($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k]);    
                   if ($k == 0) {   
                       $protein1_ion_type = 'b'.$leng;    
                   } else {
                       $protein1_ion_type = 'y'.$leng;
                       $protein1_ion_mass = $protein1_ion_mass;  // + $H2O
                   }

                   array_push($protein1_result, [    
                       'protein1_peptide'=>$protein1_peptide,    
                       'protein1_peptide_mass'=>$protein1_peptide_mass,    
                       'protein1_ion'=>$protein1_ion,    
                       'protein1_ion_mass'=>$protein1_ion_mass,   
                       'protein1_ion_type'=>$protein1_ion_type    
                   ]);
               }
           }
       }

       $protein2_result = [];    
       for ($i=0;$i<count($protein2_all);$i++) {    
           for ($j=0;$j<count($protein2_all[$i]['sequenceID_arr']);$j++) {     
               $protein2_peptide = $protein2_all[$i]['sequenceID_arr'][$j]['peptide'];    
               $protein2_peptide_mass = $protein2_all[$i]['sequenceID_arr'][$j]['peptide_mass'];    
               for ($k=0;$k<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag']);$k++) {    
                   for ($l=0;$l<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion']);$l++) {   
                       $protein2_ion = $protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l];    
                       $protein2_ion_mass = $protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion_mass'][$l];     
                       $protein2_ion_type = '';    
                       $leng = strlen($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l]);     
                       if ($l == 0) {    
                           $protein2_ion_type = 'b'.$leng;    
                       } else {
                           $protein2_ion_type = 'y'.$leng;  
                           $protein2_ion_mass = $protein2_ion_mass; // + $H2O  
                       }

                       array_push($protein2_result, [
                           'protein2_peptide'=>$protein2_peptide,     
                           'protein2_peptide_mass'=>$protein2_peptide_mass,    
                           'protein2_ion'=>$protein2_ion,    
                           'protein2_ion_mass'=>$protein2_ion_mass,    
                           'protein2_ion_type'=>$protein2_ion_type,
                           'combined_score'=>$protein2_all[$i]['combined_score']
                       ]);
                   }
               }
           }
       }


       # 15. Data intergration 
       $result = [];     
       for ($i=0;$i<count($protein1_result);$i++) {    
           $protein1_peptide = $protein1_result[$i]['protein1_peptide'];    
           $protein1_peptide_mass = $protein1_result[$i]['protein1_peptide_mass'];    
           $protein1_ion = $protein1_result[$i]['protein1_ion'];    
           $protein1_ion_mass = $protein1_result[$i]['protein1_ion_mass'];    
           $protein1_ion_type = $protein1_result[$i]['protein1_ion_type'];    

           for ($j=0;$j<count($protein2_result);$j++) {   
               $protein2_peptide = $protein2_result[$j]['protein2_peptide'];     
               $protein2_peptide_mass = $protein2_result[$j]['protein2_peptide_mass'];    
               $protein2_ion = $protein2_result[$j]['protein2_ion'];    
               $protein2_ion_mass = $protein2_result[$j]['protein2_ion_mass'];     
               $protein2_ion_type = $protein2_result[$j]['protein2_ion_type'];     

               array_push($result, [     
                       'protein1_peptide'=>$protein1_peptide,     
                       'protein1_peptide_mass'=>$protein1_peptide_mass,    
                       'protein1_ion'=>$protein1_ion,     
                       'protein1_ion_mass'=>$protein1_ion_mass,     
                       'protein1_ion_type'=>$protein1_ion_type,     
                       'protein2_peptide'=>$protein2_peptide,   
                       'protein2_peptide_mass'=>$protein2_peptide_mass,    
                       'protein2_ion'=>$protein2_ion,    
                       'protein2_ion_mass'=>$protein2_ion_mass,   
                       'protein2_ion_type'=>$protein2_ion_type,
                       'combined_score'=>$protein2_result[$j]['combined_score']
                   ]
               );
           }
       }


       # 16. Count the number of interactions at the peptide fragment level
       for ($i=0;$i<count($result);$i++) {    
           if ($crosslinker_all->cleavability == "Y") {    
               $case = mass_case_all(     
                   $crosslinker_all->binding_site,      
                   $result[$i]['protein1_peptide'], 
                   $result[$i]['protein2_peptide'],    
                   $result[$i]['protein1_peptide_mass'],    
                   $result[$i]['protein2_peptide_mass'],    
                   $crosslinker_all->mass_c,    
                   $crosslinker_all->mass_n,    
               );
           } else {    
               $case = mass_case_all(    
                   $crosslinker_all->binding_site,     
                   $result[$i]['protein1_peptide'],    
                   $result[$i]['protein2_peptide'],  
                   $result[$i]['protein1_peptide_mass'],    
                   $result[$i]['protein2_peptide_mass'],  
                   $crosslinker_all->mass,    
                   $crosslinker_all->mass,   
               );
           }
           $result[$i]['case'] = $case;    
       }


       # 17. Output data intergration
       $result_c = [];     
       for ($i=0;$i<count($result);$i++) {     
           for ($j=0;$j<count($result[$i]['case']);$j++) {   
               array_push($result_c, [     
                   'protein1_peptide'=>$result[$i]['protein1_peptide'],     
                   'protein1_peptide_mass'=>$result[$i]['protein1_peptide_mass'],    
                   'protein1_ion'=>$result[$i]['protein1_ion'],  
                   'protein1_ion_mass'=>$result[$i]['protein1_ion_mass'],     
                   'protein1_ion_type'=>$result[$i]['protein1_ion_type'],   
                   'protein2_peptide'=>$result[$i]['protein2_peptide'],     
                   'protein2_peptide_mass'=>$result[$i]['protein2_peptide_mass'],     
                   'protein2_ion'=>$result[$i]['protein2_ion'],    
                   'protein2_ion_mass'=>$result[$i]['protein2_ion_mass'],     
                   'protein2_ion_type'=>$result[$i]['protein2_ion_type'],    
                   'protein1_peptide_c_term_mass'=>$result[$i]['case'][$j]['protein1_c_term_mass'],     
                   'protein2_peptide_n_term_mass'=>$result[$i]['case'][$j]['protein2_n_term_mass'],     
                   'protein1_peptide_n_term_mass'=>$result[$i]['case'][$j]['protein1_n_term_mass'],    
                   'protein2_peptide_c_term_mass'=>$result[$i]['case'][$j]['protein2_c_term_mass'],
                   'combined_score'=>$result[$i]['combined_score']
               ]);
           }
       }
       $result = $result_c; 
       


       # 18. Count the number of interactions at the ion fragment level
       for ($i=0;$i<count($result);$i++) {    
           if ($crosslinker_all->cleavability == "Y") {  
               $case = mass_case_all(     
                   $crosslinker_all->binding_site,    
                   $result[$i]['protein1_ion'],   
                   $result[$i]['protein2_ion'],   
                   $result[$i]['protein1_ion_mass'],  
                   $result[$i]['protein2_ion_mass'],   
                   $crosslinker_all->mass_c,    
                   $crosslinker_all->mass_n,
               );
           } else {   
               $case = mass_case_all(   
                   $crosslinker_all->binding_site,    
                   $result[$i]['protein1_ion'],    
                   $result[$i]['protein2_ion'],  
                   $result[$i]['protein1_ion_mass'],    
                   $result[$i]['protein2_ion_mass'],      
                   $crosslinker_all->mass,   
                   $crosslinker_all->mass,    
               );
           }
           $result[$i]['case'] = $case;    
       }


       # 19. Output data intergration
       $result_c = [];    
       for ($i=0;$i<count($result);$i++) {    
           for ($j=0;$j<count($result[$i]['case']);$j++) {    
               array_push($result_c, [     
                   'protein1_peptide'=>$result[$i]['protein1_peptide'],   
                   'protein1_peptide_mass'=>$result[$i]['protein1_peptide_mass'],    
                   'protein1_ion'=>$result[$i]['protein1_ion'],     
                   'protein1_ion_mass'=>$result[$i]['protein1_ion_mass'],    
                   'protein1_ion_type'=>$result[$i]['protein1_ion_type'],   
                   'protein2_peptide'=>$result[$i]['protein2_peptide'],    
                   'protein2_peptide_mass'=>$result[$i]['protein2_peptide_mass'],    
                   'protein2_ion'=>$result[$i]['protein2_ion'],     
                   'protein2_ion_mass'=>$result[$i]['protein2_ion_mass'],    
                   'protein2_ion_type'=>$result[$i]['protein2_ion_type'],    
                   'protein1_peptide_c_term_mass'=>$result[$i]['protein1_peptide_c_term_mass'],    
                   'protein2_peptide_n_term_mass'=>$result[$i]['protein2_peptide_n_term_mass'],    
                   'protein1_peptide_n_term_mass'=>$result[$i]['protein1_peptide_n_term_mass'],     
                   'protein2_peptide_c_term_mass'=>$result[$i]['protein2_peptide_c_term_mass'],    
                   'protein1_ion_c_term_mass'=>$result[$i]['case'][$j]['protein1_c_term_mass'],   
                   'protein2_ion_n_term_mass'=>$result[$i]['case'][$j]['protein2_n_term_mass'],    
                   'protein1_ion_n_term_mass'=>$result[$i]['case'][$j]['protein1_n_term_mass'],   
                   'protein2_ion_c_term_mass'=>$result[$i]['case'][$j]['protein2_c_term_mass'],
                   'peptidecharge'=>'-',
                   'ioncharge'=>'-',
                   'combined_score'=>$result[$i]['combined_score']
               ]);
           }
       }
       $result = $result_c; 


       # 20. Data assign to export to front-end
       $result_c = [];    
       $result_id = 1;

       # Combinded score Setting
       for ($i=0;$i<count($result);$i++) {   

           $chk_remove2 = 'N';
           if (!empty($score_min)) {
               if ($result[$i]['combined_score'] < $score_min) {
                   $chk_remove2 = 'Y';
               }
           } 
           if (!empty($score_max)) {
               if ($result[$i]['combined_score'] >= $score_max) {
                   $chk_remove2 = 'Y';
               }
           }

           if ($chk_remove2 == 'N') {

               $protein1_peptide_mass = $result[$i]['protein1_peptide_mass']; 
               $protein1_ion_mass = $result[$i]['protein1_ion_mass'];     
               $protein2_peptide_mass = $result[$i]['protein2_peptide_mass'];    
               $protein2_ion_mass = $result[$i]['protein2_ion_mass'];    

               $protein1_peptide_c_term_mass = $result[$i]['protein1_peptide_c_term_mass'];    
               $protein2_peptide_n_term_mass = $result[$i]['protein2_peptide_n_term_mass'];    
               $protein1_peptide_n_term_mass = $result[$i]['protein1_peptide_n_term_mass'];    
               $protein2_peptide_c_term_mass = $result[$i]['protein2_peptide_c_term_mass'];    

               $protein1_ion_c_term_mass = $result[$i]['protein1_ion_c_term_mass'];     
               $protein2_ion_n_term_mass = $result[$i]['protein2_ion_n_term_mass'];     
               $protein1_ion_n_term_mass = $result[$i]['protein1_ion_n_term_mass'];     
               $protein2_ion_c_term_mass = $result[$i]['protein2_ion_c_term_mass'];  
               



               # 20-1. Modification : Carbamidomethyl (C) - Calculate mass of change 
               if ($carbamidomethyl_c_static == 'Y' || $carbamidomethyl_c_variable == 'Y') {
                   if (strpos($result[$i]['protein1_peptide'],  $carbamidomethyl_c_str) !== FALSE) {
                       $protein1_peptide_mass = $protein1_peptide_mass + $carbamidomethyl_c_mass;     
                       $protein1_peptide_c_term_mass = $protein1_peptide_c_term_mass + $carbamidomethyl_c_mass;    
                       $protein1_peptide_n_term_mass = $protein1_peptide_n_term_mass + $carbamidomethyl_c_mass;  
                   }
               }

               if ($carbamidomethyl_c_static == 'Y' || $carbamidomethyl_c_variable == 'Y') {
                   if (strpos($result[$i]['protein2_peptide'],  $carbamidomethyl_c_str) !== FALSE) {
                   $protein2_peptide_mass = $protein2_peptide_mass + $carbamidomethyl_c_mass;    
                   $protein2_peptide_n_term_mass = $protein2_peptide_n_term_mass + $carbamidomethyl_c_mass;    
                   $protein2_peptide_c_term_mass = $protein2_peptide_c_term_mass + $carbamidomethyl_c_mass;   
                   }
               }

               if ($carbamidomethyl_c_static == 'Y' || $carbamidomethyl_c_variable == 'Y') {
                   if (strpos($result[$i]['protein1_ion'],  $carbamidomethyl_c_str) !== FALSE) {
                   $protein1_ion_mass = $protein1_ion_mass + $carbamidomethyl_c_mass;    
                   $protein1_ion_c_term_mass = $protein1_ion_c_term_mass + $carbamidomethyl_c_mass;   
                   $protein1_ion_n_term_mass = $protein1_ion_n_term_mass + $carbamidomethyl_c_mass;    
                   }
               }           
               
               if ($carbamidomethyl_c_static == 'Y' || $carbamidomethyl_c_variable == 'Y') {
                   if (strpos($result[$i]['protein2_ion'],  $carbamidomethyl_c_str) !== FALSE) {
                   $protein2_ion_mass = $protein2_ion_mass + $carbamidomethyl_c_mass;     
                   $protein2_ion_n_term_mass = $protein2_ion_n_term_mass + $carbamidomethyl_c_mass;    
                   $protein2_ion_c_term_mass = $protein2_ion_c_term_mass + $carbamidomethyl_c_mass;  
                   }
               }

               # 20-2. Modification : Oxidation (M)) - Calculate mass of change 

               if ($oxidation_m_static == 'Y' || $oxidation_m_variable == 'Y') {
                   if (strpos($result[$i]['protein1_peptide'], $oxidation_m_str) !== FALSE) {
                   $protein1_peptide_mass = $protein1_peptide_mass + $oxidation_m_mass;    
                   $protein1_peptide_c_term_mass = $protein1_peptide_c_term_mass + $oxidation_m_mass;    
                   $protein1_peptide_n_term_mass = $protein1_peptide_n_term_mass + $oxidation_m_mass;      
                   }
               }

               if ($oxidation_m_static == 'Y' || $oxidation_m_variable == 'Y') {
                   if (strpos($result[$i]['protein2_peptide'], $oxidation_m_str) !== FALSE) {
                   $protein2_peptide_mass = $protein2_peptide_mass + $oxidation_m_mass;   
                   $protein2_peptide_n_term_mass = $protein2_peptide_n_term_mass + $oxidation_m_mass;   
                   $protein2_peptide_c_term_mass = $protein2_peptide_c_term_mass + $oxidation_m_mass;      
                   }
               }

               if ($oxidation_m_static == 'Y' || $oxidation_m_variable == 'Y') {
                   if (strpos($result[$i]['protein1_ion'], $oxidation_m_str) !== FALSE) {
                   $protein1_ion_mass = $protein1_ion_mass + $oxidation_m_mass;    
                   $protein1_ion_c_term_mass = $protein1_ion_c_term_mass + $oxidation_m_mass;   
                   $protein1_ion_n_term_mass = $protein1_ion_n_term_mass + $oxidation_m_mass;         
                   }
               }
             
               if ($oxidation_m_static == 'Y' || $oxidation_m_variable == 'Y') {
                   if (strpos($result[$i]['protein2_ion'], $oxidation_m_str) !== FALSE) {
                   $protein2_ion_mass = $protein2_ion_mass + $oxidation_m_mass;   
                   $protein2_ion_n_term_mass = $protein2_ion_n_term_mass + $oxidation_m_mass;    
                   $protein2_ion_c_term_mass = $protein2_ion_c_term_mass + $oxidation_m_mass;          
                   }
               }

               # 20-3. Peptide, Ion Charge : Calculate mass of change
               for ($j=0;$j<count($peptidecharge);$j++) {
                   for ($k=0;$k<count($ioncharge);$k++) {

                       # 20-4. Count Static, Variable of Modifications 
                       if ($carbamidomethyl_c_variable == 'Y' || $oxidation_m_variable == 'Y') {    
                           if ( 
                               strpos($result[$i]['protein1_peptide'], $carbamidomethyl_c_str) !== FALSE ||    
                               strpos($result[$i]['protein1_ion'], $carbamidomethyl_c_str) !== FALSE ||    
                               strpos($result[$i]['protein2_peptide'], $carbamidomethyl_c_str) !== FALSE ||    
                               strpos($result[$i]['protein2_ion'], $carbamidomethyl_c_str) !== FALSE ||    
                               strpos($result[$i]['protein1_peptide'], $oxidation_m_str) !== FALSE ||    
                               strpos($result[$i]['protein1_ion'], $oxidation_m_str) !== FALSE ||    
                               strpos($result[$i]['protein2_peptide'], $oxidation_m_str) !== FALSE ||   
                               strpos($result[$i]['protein2_ion'], $oxidation_m_str) !== FALSE    
                           ) { 
                               array_push($result_c, [
                                   'block'=>'A',  
                                   'id'=>$result_id,
                                   'combined_score'=>$result[$i]['combined_score'] / 1000,
                                   'protein1_peptide'=>$result[$i]['protein1_peptide'],
                                   'crosslinker'=>$crosslinker_all->name,
                                   'protein2_peptide'=>$result[$i]['protein2_peptide'],
                                   'peptidecharge'=>$peptidecharge[$j],

                                   'protein1_peptide_c_term_mass'=>round($Proton + ($result[$i]['protein1_peptide_c_term_mass'] + $H2O) / $peptidecharge[$j], 4),                                   
                                   'center_mass_peptide_1'=>round($crosslinker_all->mass_center / $peptidecharge[$j], 4),
                                   'protein2_peptide_n_term_mass'=>round($Proton + ($result[$i]['protein2_peptide_n_term_mass'] + $H2O) / $peptidecharge[$j], 4),
                                   'protein1_peptide_n_term_mass'=>round($Proton + ($result[$i]['protein1_peptide_n_term_mass'] + $H2O) / $peptidecharge[$j], 4),
                                   'center_mass_peptide_2'=>round($crosslinker_all->mass_center / $peptidecharge[$j], 4),
                                   'protein2_peptide_c_term_mass'=>round($Proton + ($result[$i]['protein2_peptide_c_term_mass'] + $H2O) / $peptidecharge[$j], 4),

                                   'protein1_ion_type'=>$result[$i]['protein1_ion_type'],  
                                   'protein1_ion'=>$result[$i]['protein1_ion'],   
                                   'protein2_ion_type'=>$result[$i]['protein2_ion_type'],  
                                   'protein2_ion'=>$result[$i]['protein2_ion'], 
                                   'ioncharge'=>$ioncharge[$k],

                                   'protein1_ion_c_term_mass'=>round($Proton + ($result[$i]['protein1_ion_c_term_mass'] + $H2O)/ $ioncharge[$k], 4),                                      
                                   'center_mass_ion_1'=>round($crosslinker_all->mass_center / $ioncharge[$k], 4),
                                   'protein2_ion_n_term_mass'=>round($Proton + ($result[$i]['protein2_ion_n_term_mass'] + $H2O) / $ioncharge[$k], 4),   
                                   'protein1_ion_n_term_mass'=>round($Proton + ($result[$i]['protein1_ion_n_term_mass'] + $H2O) / $ioncharge[$k], 4),  
                                   'center_mass_ion_2'=>round($crosslinker_all->mass_center / $ioncharge[$k], 4),
                                   'protein2_ion_c_term_mass'=>round($Proton + ($result[$i]['protein2_ion_c_term_mass'] + $H2O) / $ioncharge[$k], 4)
                               ]);

                               $result_id = $result_id + 1;
                           }   
                       }

                       # 20-5. Calculate mass value considering peptide / ion charge
                       // $protein1_peptide_mass2 = $protein1_peptide_mass / $peptidecharge[$j];
                       // $protein2_peptide_mass2 = $protein2_peptide_mass / $peptidecharge[$j];     


                       # Originaal code
                       // $protein1_peptide_c_term_mass2 = $Proton + (($protein1_peptide_c_term_mass + $H2O) / $peptidecharge[$j]);    
                       // $protein2_peptide_n_term_mass2 = $Proton + (($protein2_peptide_n_term_mass + $H2O) / $peptidecharge[$j]);    
                       // $protein1_peptide_n_term_mass2 = $Proton + (($protein1_peptide_n_term_mass + $H2O) / $peptidecharge[$j]);    
                       // $protein2_peptide_c_term_mass2 = $Proton + (($protein2_peptide_c_term_mass + $H2O) / $peptidecharge[$j]);     


                       $protein1_peptide_c_term_mass2 = $protein1_peptide_c_term_mass;    
                       $protein2_peptide_n_term_mass2 = $protein2_peptide_n_term_mass;    
                       $protein1_peptide_n_term_mass2 = $protein1_peptide_n_term_mass;    
                       $protein2_peptide_c_term_mass2 = $protein2_peptide_c_term_mass;    


                       // $protein1_ion_mass2 = $protein1_ion_mass / $ioncharge[$k];  
                       // $protein2_ion_mass2 = $protein2_ion_mass / $ioncharge[$k];     

                       # Original code
                       // $protein1_ion_c_term_mass2 = $Proton + ($protein1_ion_c_term_mass + $H2O) / $ioncharge[$k];    
                       // $protein2_ion_n_term_mass2 = $Proton + ($protein2_ion_n_term_mass + $H2O)/ $ioncharge[$k];    
                       // $protein1_ion_n_term_mass2 = $Proton + ($protein1_ion_n_term_mass + $H2O) / $ioncharge[$k];    
                       // $protein2_ion_c_term_mass2 = $Proton + ($protein2_ion_c_term_mass + $H2O) / $ioncharge[$k];    
                       
                       $protein1_ion_c_term_mass2 = $protein1_ion_c_term_mass;    
                       $protein2_ion_n_term_mass2 = $protein2_ion_n_term_mass;    
                       $protein1_ion_n_term_mass2 = $protein1_ion_n_term_mass;    
                       $protein2_ion_c_term_mass2 = $protein2_ion_c_term_mass; 

                       
                       array_push($result_c, [
                           'block'=>'B',     
                           'id'=>$result_id,
                           'combined_score'=>$result[$i]['combined_score'] / 1000,
                           'protein1_peptide'=>$result[$i]['protein1_peptide'],    
                           'crosslinker'=>$crosslinker_all->name,
                           'protein2_peptide'=>$result[$i]['protein2_peptide'], 
                           'peptidecharge'=>$peptidecharge[$j], 


                           'protein1_peptide_c_term_mass'=>round($Proton + ($protein1_peptide_c_term_mass2 + $H2O) / $peptidecharge[$j], 4),   
                           'center_mass_peptide_1'=>round($crosslinker_all->mass_center / $peptidecharge[$j], 4),
                           'protein2_peptide_n_term_mass'=>round($Proton + ($protein2_peptide_n_term_mass2 + $H2O) / $peptidecharge[$j], 4),   
                           'protein1_peptide_n_term_mass'=>round($Proton + ($protein1_peptide_n_term_mass2 + $H2O) / $peptidecharge[$j], 4),  
                           'center_mass_peptide_2'=>round($crosslinker_all->mass_center / $peptidecharge[$j], 4),
                           'protein2_peptide_c_term_mass'=>round($Proton + ($protein2_peptide_c_term_mass2 + $H2O) / $peptidecharge[$j], 4),   

                           'protein1_ion_type'=>$result[$i]['protein1_ion_type'],  
                           'protein1_ion'=>$result[$i]['protein1_ion'],    
                           'protein2_ion_type'=>$result[$i]['protein2_ion_type'],  
                           'protein2_ion'=>$result[$i]['protein2_ion'],     
                           'ioncharge'=>$ioncharge[$k],

                           'protein1_ion_c_term_mass'=>round($Proton + ($protein1_ion_c_term_mass2 + $H2O) / $ioncharge[$k], 4),  
                           'center_mass_ion_1'=>round($crosslinker_all->mass_center / $ioncharge[$k], 4),
                           'protein2_ion_n_term_mass'=>round($Proton + ($protein2_ion_n_term_mass2 + $H2O) / $ioncharge[$k], 4),   
                           'protein1_ion_n_term_mass'=>round($Proton + ($protein1_ion_n_term_mass2 + $H2O) / $ioncharge[$k], 4), 
                           'center_mass_ion_2'=>round($crosslinker_all->mass_center / $ioncharge[$k], 4),
                           'protein2_ion_c_term_mass'=>round($Proton + ($protein2_ion_c_term_mass2 + $H2O) / $ioncharge[$k], 4),
                       ]);
                       $result_id = $result_id + 1;  
                   }
               } 
           }
       }

       $result = $result_c;   
       unset($result_c);

        

        
#####################################################################


        # Button of Export Data

        $filename = 'Result.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $handle = fopen('php://output', 'w');
        // $columns = $Index."\t".$score."\t".$Protein1_Peptide."\t".$Cross_Linker."\t".$Protrin2_Peptide."\t".$Peptide_Charge."\t".$Protein1_C_trem_Mass."\t".$Center_Mass."\t".$Protein2_N_term_Mass."\t".$Protein1_N_term_Mass."\t".$Center_Mass."\t".$Protein2_C_term_Mass."\t".$Ion_Charge."\t".$Protein1_Ion_type."\t".$Protein1_Ion."\t".$Protein2_Ion_type."\t".$Protein2_Ion."\t".$Protein1_Ion_C_term_Mass."\t".$Center_Mass."\t".$Protein2_Ion_N_term_Mass."\t".$Protein1_N_term_Mass."\t".$Center_Mass."\t".$Protein2_C_term_Mass."\n";
        // fwrite($handle, $columns);
        fputs($handle, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        foreach ($result as $fields) {
            fputcsv($handle, $fields);
        }

        fclose($handle);         
    }
}
?>