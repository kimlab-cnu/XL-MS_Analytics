<?php ob_start();
defined('BASEPATH') OR exit('No direct script access allowed');
class search extends CI_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->database();// connect to DB
    }
    public function index(){
        $data['enzyme']=$this->db->query('select id,name from enzyme')->result();
        $data['crosslinker']=$this->db->query('select id,name from crosslinker')->result();
        $modification=get_modification();
        $data['modification']=$modification;
        // loading frontend page (search page)
        $this->load->view('common/header');
        $this->load->view('search/search',$data);
        $this->load->view('common/footer');
    }
    public function result(){
        // input information from search page
        $human_protein=$this->input->post('human_protein_reviewed');
        $enzyme=$this->input->post('enzyme');
        $crosslinker=$this->input->post('crosslinker');
        $peptide_length_min=$this->input->post('peptide_length_min');
        $peptide_length_max=$this->input->post('peptide_length_max');
        $ranking=$this->input->post('sorting');
        $peptidecharge=$this->input->post('peptidecharge');
        $ioncharge=$this->input->post('ioncharge');
        $modification=get_modification();
        $data['modification']=$modification;
        $carbamidomethyl=$this->input->post('carbamidomethyl');
        $oxidation=$this->input->post('oxidation');
        $proton=1.00727649;
        $h2o=18.01056468403;
        // information for summary function on Result page (summarize by Combined Score)
        $score_min=$this->input->post('score_min');
        $score_max=$this->input->post('score_max');
        if (!empty($score_min)){
            $score_min=$score_min*1000;
        }
        if (!empty($score_max)){
            $score_max=$score_max*1000;
        }
        // information for summary function on Result page (summarize by Lists of peptide from Prego)
        $p1_hp_peptide=$this->input->post('p1_hp_peptide');
        if ($p1_hp_peptide != ""){
            $p1_hp_peptide=strtoupper($p1_hp_peptide);
        }
        $p2_hp_peptide=$this->input->post('p2_hp_peptide');
        if ($p2_hp_peptide != ""){
            $p2_hp_peptide=strtoupper($p2_hp_peptide);
        }
        // Data query from DB
        $query='select name,string,sequenceID from human_protein_reviewed where entrynumber="'.$human_protein.'"';
        $protein1_all=$this->db->query($query)->result()[0];
        // in case of no data in DB
        if (empty($protein1_all)){
            header("Location:/search/noresult?s=".$human_protein);
            die();
        }
        if ($protein1_all->string == "NaN"){
            header("Location:/search/nointeraction?s=".$human_protein);
            die();
        }
        
        $query='select * from enzyme where id="'.$enzyme.'"';
        $enzyme_all=$this->db->query($query)->result()[0];
        $query='select name,binding_site,cleavability,mass,mass_c,mass_center,mass_n from crosslinker where id="'.$crosslinker.'"';
        $crosslinker_all=$this->db->query($query)->result()[0];
        $query='select * from amino_acid_mass';
        $aa_mass_all=$this->db->query($query)->result();
        $query='select protein2,combined_score from protein_interaction where protein1="'.$protein1_all->string.'" order by combined_score desc limit '.$ranking;
        $protein2_interaction_all=$this->db->query($query)->result();
        $protein2_string='';
        
        for ($i=0;$i<count($protein2_interaction_all);$i++){
            if ($i != 0){
                $protein2_string.=',';
            }
            $protein2_string.='"';
            $protein2_string.=$protein2_interaction_all[$i]->protein2;
            $protein2_string.='"';
        }
        
        $query='select name,entrynumber,entryname,string,sequenceID from human_protein_reviewed where string IN ('.$protein2_string.') AND entrynumber not like "%-%"';// 문자열 형태로 변환한 상호작용 단백질들 정보로 각 상호작용하는 단백질들의 정보 DB 쿼리
        $protein2_all_query=$this->db->query($query)->result();
        
        $protein2_all=[];
        for ($i=0;$i<count($protein2_interaction_all);$i++){
            for ($j=0;$j<count($protein2_all_query);$j++){
                if ($protein2_interaction_all[$i]->protein2==$protein2_all_query[$j]->string){
                    $arr=[];
                    $arr=[
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
        unset($protein2_all_query);
        $interaction_info=[];
        for ($i=0;$i<count($protein2_all);$i++){
            $chk_cs='N';
            if (!empty($score_min)){
                if ($protein2_all[$i]['combined_score']<$score_min){
                    $chk_cs='Y';
                }
            }
            if (!empty($score_max)){
                if ($protein2_all[$i]['combined_score']>=$score_max){
                    $chk_cs='Y';
                }
            }
            if ($chk_cs=='N'){
                for ($j=0;$j<count($protein2_interaction_all);$j++){
                    if ($protein2_all[$i]['string']==$protein2_interaction_all[$j]->protein2){
                        array_push($interaction_info, [
                            'name'=>$protein2_all[$i]['name'],
                            'score'=>$protein2_interaction_all[$j]->combined_score
                        ]);
                    }
                }
            }
        }
        $data['interaction_info']=$interaction_info;
        $sequenceID_arr=seq_digestion($protein1_all->sequenceID,$enzyme_all->cleavage_site,$enzyme_all->exception,$crosslinker_all->binding_site);
        $sequenceID_arr_unset=[];
        for ($i=0;$i<count($sequenceID_arr);$i++){
            if (strlen($sequenceID_arr[$i]['peptide'])>=$peptide_length_min&&strlen($sequenceID_arr[$i]['peptide'])<=$peptide_length_max){
                array_push($sequenceID_arr_unset,['peptide'=>$sequenceID_arr[$i]['peptide']]);
            }
        }
        $sequenceID_arr=$sequenceID_arr_unset;
        unset($sequenceID_arr_unset);
        for ($i=0;$i<count($sequenceID_arr);$i++){
            $peptide_mass=(double)0;
            for ($j=0;$j<strlen($sequenceID_arr[$i]['peptide']);$j++){
                for ($k=0;$k<count($aa_mass_all);$k++){
                    if ($sequenceID_arr[$i]['peptide'][$j]==$aa_mass_all[$k]->slc){
                        $peptide_mass=$peptide_mass+(double)$aa_mass_all[$k]->monoisotopic;
                        if ($j==strlen($sequenceID_arr[$i]['peptide'])-1){
                            $peptide_mass=$peptide_mass;
                        }
                    }
                }
                $sequenceID_arr[$i]['peptide_mass']=$peptide_mass;
            }

            // convert to target protein (precursor ion / MS1) to fragmented ion(product ion / MS2)
            $peptide_frag=[];
            for ($j=0;$j<strlen($sequenceID_arr[$i]['peptide']);$j++){
                if ($j>0 && $j<strlen($sequenceID_arr[$i]['peptide'])){
                    $ion=[];
                    $b_ion="";
                    $y_ion="";
                    $b_ion=substr($sequenceID_arr[$i]['peptide'],0,$j);
                    $y_ion=substr($sequenceID_arr[$i]['peptide'],$j);
                    array_push($ion,$b_ion);
                    array_push($ion,$y_ion);
                    $mass=[];
                    $mass_b=(double)0;
                    for ($k=0;$k<strlen($b_ion);$k++){
                        for ($l=0;$l<count($aa_mass_all);$l++){
                            if ($b_ion[$k]==$aa_mass_all[$l]->slc){
                                $mass_b=$mass_b+(double)$aa_mass_all[$l]->monoisotopic;
                            }
                        }
                    }
                    array_push($mass,$mass_b);
                    $mass_y=(double)0;
                    for ($k=0;$k<strlen($y_ion);$k++){
                        for ($l=0;$l<count($aa_mass_all);$l++){
                            if ($y_ion[$k]==$aa_mass_all[$l]->slc){
                                $mass_y=$mass_y+(double)$aa_mass_all[$l]->monoisotopic;
                            }
                        }
                    }
                    array_push($mass,$mass_y);
                    array_push($peptide_frag,['ion'=>$ion, 'ion_mass'=>$mass]);
                }
            }
            $sequenceID_arr[$i]['peptide_frag']=$peptide_frag;
            unset($peptide_frag);
        }
        $protein1_all->sequenceID_arr=$sequenceID_arr;
        
        for ($i=0;$i<count($protein2_all);$i++){
            $sequenceID_arr=seq_digestion($protein2_all[$i]['sequenceID'],$enzyme_all->cleavage_site,$enzyme_all->exception,$crosslinker_all->binding_site);
            $sequenceID_arr_unset=[];
            for ($j=0;$j<count($sequenceID_arr);$j++){
                if (strlen($sequenceID_arr[$j]['peptide'])>=$peptide_length_min && strlen($sequenceID_arr[$j]['peptide'])<=$peptide_length_max){
                    array_push($sequenceID_arr_unset, ['peptide'=>$sequenceID_arr[$j]['peptide']]);
                }
            }
            $sequenceID_arr=$sequenceID_arr_unset;
            unset($sequenceID_arr_unset);
            for ($j=0;$j<count($sequenceID_arr);$j++){
                $peptide_mass=(double)0;
                for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++){
                    for ($l=0;$l<count($aa_mass_all);$l++){
                        if ($sequenceID_arr[$j]['peptide'][$k]==$aa_mass_all[$l]->slc){
                            $peptide_mass=$peptide_mass+(double)$aa_mass_all[$l]->monoisotopic;
                        }
                    }
                }
                $sequenceID_arr[$j]['peptide_mass']=$peptide_mass;
                // peptides of interacted protein (percursor ion / MS1) convert to fragmented ion (product ion / MS2)
                $peptide_frag=[];
                for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++){
                    if ($k>0 && $k<strlen($sequenceID_arr[$j]['peptide'])){
                        $ion=[];
                        $b_ion='';
                        $y_ion='';
                        $b_ion=substr($sequenceID_arr[$j]['peptide'],0,$k);
                        $y_ion=substr($sequenceID_arr[$j]['peptide'],$k);
                        array_push($ion,$b_ion);
                        array_push($ion,$y_ion);
                        $mass=[];
                        $mass_b=(double)0;
                        for ($l=0;$l<strlen($b_ion);$l++){
                            for ($m=0;$m<count($aa_mass_all);$m++){
                                if ($b_ion[$l]==$aa_mass_all[$m]->slc){
                                    $mass_b=$mass_b+(double)$aa_mass_all[$m]->monoisotopic;
                                }
                            }
                        }
                        array_push($mass,$mass_b);
                        $mass_y=(double)0;
                        for ($l=0;$l<strlen($y_ion);$l++){
                            for ($m=0;$m<count($aa_mass_all);$m++){
                                if ($y_ion[$l]==$aa_mass_all[$m]->slc){
                                    $mass_y=$mass_y+(double)$aa_mass_all[$m]->monoisotopic;
                                }
                            }
                        }
                        array_push($mass,$mass_y);
                        array_push($peptide_frag, ['ion'=>$ion, 'ion_mass'=>$mass]);
                    }
                }
                $sequenceID_arr[$j]['peptide_frag']=$peptide_frag;
                unset($peptide_frag);
            }
            $protein2_all[$i]['sequenceID_arr']=$sequenceID_arr;
        }
        $protein1_result=[];
        for ($i=0;$i<count($protein1_all->sequenceID_arr);$i++){
            $protein1_peptide=$protein1_all->sequenceID_arr[$i]['peptide'];
            $protein1_peptide_mass=$protein1_all->sequenceID_arr[$i]['peptide_mass'];
            for ($j=0;$j<count($protein1_all->sequenceID_arr[$i]['peptide_frag']);$j++){
                for ($k=0;$k<count($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion']);$k++){
                    $protein1_ion=$protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k];
                    $protein1_ion_mass=$protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion_mass'][$k];
                    $protein1_ion_type='';
                    $leng=strlen($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k]);
                    if ($k==0){
                        $protein1_ion_type='b'.$leng;
                    } else{
                        $protein1_ion_type='y'.$leng;
                        $protein1_ion_mass=$protein1_ion_mass+$h2o;
                    }
                    array_push($protein1_result, [
                        'protein1_peptide'=>$protein1_peptide,
                        'protein1_peptide_mass'=>$protein1_peptide_mass,
                        'protein1_ion'=>$protein1_ion,
                        'protein1_ion_type'=>$protein1_ion_type,
                        'protein1_ion_mass'=>$protein1_ion_mass
                    ]);
                }
            }
        }
        $protein2_result=[];
        for ($i=0;$i<count($protein2_all);$i++){
            for ($j=0;$j<count($protein2_all[$i]['sequenceID_arr']);$j++){
                $protein2_peptide=$protein2_all[$i]['sequenceID_arr'][$j]['peptide'];
                $protein2_peptide_mass=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_mass'];
                for ($k=0;$k<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag']);$k++){
                    for ($l=0;$l<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion']);$l++){
                        $protein2_ion=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l];
                        $protein2_ion_mass=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion_mass'][$l];
                        $protein2_ion_type='';
                        $leng=strlen($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l]);
                        if ($l==0){
                            $protein2_ion_type='b'.$leng;
                        } else{
                            $protein2_ion_type='y'.$leng;
                            $protein2_ion_mass=$protein2_ion_mass+$h2o;
                        }
                        array_push($protein2_result,[
                            'protein2_peptide'=>$protein2_peptide,
                            'protein2_peptide_mass'=>$protein2_peptide_mass,
                            'protein2_ion'=>$protein2_ion,
                            'protein2_ion_type'=>$protein2_ion_type,
                            'protein2_ion_mass'=>$protein2_ion_mass,
                            'combined_score'=>$protein2_all[$i]['combined_score']
                        ]);
                    }
                }
            }
        }
        $result=[];
        for ($i=0;$i<count($protein1_result);$i++){
            $protein1_peptide=$protein1_result[$i]['protein1_peptide'];
            $protein1_peptide_mass=$protein1_result[$i]['protein1_peptide_mass'];
            $protein1_ion=$protein1_result[$i]['protein1_ion'];
            $protein1_ion_type=$protein1_result[$i]['protein1_ion_type'];
            $protein1_ion_mass=$protein1_result[$i]['protein1_ion_mass'];
            for ($j=0;$j<count($protein2_result);$j++){
                $protein2_peptide=$protein2_result[$j]['protein2_peptide'];
                $protein2_peptide_mass=$protein2_result[$j]['protein2_peptide_mass'];
                $protein2_ion=$protein2_result[$j]['protein2_ion'];
                $protein2_ion_type=$protein2_result[$j]['protein2_ion_type'];
                $protein2_ion=$protein2_result[$j]['protein2_ion_mass'];
                array_push($result, [
                    'protein1_peptide'=>$protein1_peptide,
                    'protein1_peptide_mass'=>$protein1_peptide_mass,
                    'protein1_ion'=>$protein1_ion,
                    'protein1_ion_type'=>$protein1_ion_type,
                    'protein1_ion_mass'=>$protein1_ion_mass,
                    'protein2_peptide'=>$protein2_peptide,
                    'protein2_peptide_mass'=>$protein2_peptide_mass,
                    'protein2_ion'=>$protein2_ion,
                    'protein2_ion_type'=>$protein2_ion_type,
                    'protein2_ion_mass'=>$protein2_ion_mass,
                    'combined_score'=>$protein2_result[$j]['combined_score']
                ]);
            }
        }
        unset($protein1_result);
        unset($protein2_result);
        unset($protein1_peptide);
        unset($protein1_peptide_mass);
        unset($protein1_ion);
        unset($protein1_ion_type);
        unset($protein1_ion_mass);
        unset($protein2_peptide);
        unset($protein2_peptide_mass);
        unset($protein2_ion);
        unset($protein2_ion_type);
        unset($protein2_ion_mass);
        // all case of Cross linker binding in protein level (precursor ion level)
        for ($i=0;$i<count($result);$i++){
            if ($crosslinker_all->cleavability=="Y"){
                $case=mass_case_all(
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_peptide'],
                    $result[$i]['protein2_peptide'],
                    $result[$i]['protein1_peptide_mass'],
                    $result[$i]['protein2_peptide_mass'],
                    $crosslinker_all->mass_c,
                    $crosslinker_all->mass_n
                );
            } else{
                $case=mass_case_all(
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_peptide'],
                    $result[$i]['protein2_peptide'],
                    $result[$i]['protein1_peptide_mass'],
                    $result[$i]['protein2_peptide_mass'],
                    $crosslinker_all->mass,
                    $crosslinker_all->mass
                );
            }
            $result[$i]['case']=$case;
        }
        $result_c=[];
        for ($i=0;$i<count($result);$i++){
            for ($j=0;$j<count($result[$i]['case']);$j++){
                array_push($result_c,[
                    'protein1_peptide'=>$result[$i]['protein1_peptide'],
                    'protein1_peptide_mass'=>$result[$i]['protein1_peptide_mass'],
                    'protein1_ion'=>$result[$i]['protein1_ion'],
                    'protein1_ion_type'=>$result[$i]['protein1_ion_type'],
                    'protein1_ion_mass'=>$result[$i]['protein1_ion_mass'],
                    'protein2_peptide'=>$result[$i]['protein2_peptide'],
                    'protein2_peptide_mass'=>$result[$i]['protein2_peptide_mass'],
                    'protein2_ion'=>$result[$i]['protein2_ion'],
                    'protein2_ion_type'=>$result[$i]['protein2_ion_type'],
                    'protein2_ion_mass'=>$result[$i]['protein2_ion_mass'],
                    'protein1_peptide_c_term_mass'=>$result[$i]['case'][$j]['protein1_c_term_mass'],
                    'protein2_peptide_n_term_mass'=>$result[$i]['case'][$j]['protein2_n_term_mass'],
                    'protein1_peptide_n_term_mass'=>$result[$i]['case'][$j]['protein1_n_term_mass'],
                    'protein2_peptide_c_term_mass'=>$result[$i]['case'][$j]['protein2_c_term_mass'],
                    'combined_score'=>$result[$i]['combined_score']
                ]);
            }
        }
        $result=$result_c;
        // all case of Cross linker binding in peptide level (product ion level)
        for ($i=0;$i<count($result);$i++){
            if ($crosslinker_all->cleavability=="Y"){
                $case=mass_case_all(
                    $crosslinker_all->binding_site,// 
                    $result[$i]['protein1_ion'],
                    $result[$i]['protein2_ion'],
                    $result[$i]['protein1_ion_mass'],
                    $result[$i]['protein2_ion_mass'],
                    $crosslinker_all->mass_c,
                    $crosslinker_all->mass_n
                );
            } else{
                $case=mass_case_all(
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_ion'],
                    $result[$i]['protein2_ion'],
                    $result[$i]['protein1_ion_mass'],
                    $result[$i]['protein2_ion_mass'],
                    $crosslinker_all->mass,
                    $crosslinker_all->mass
                );
            }
            $result[$i]['case']=$case;
        }
        
        $result_c=[];
        for ($i=0;$i<count($result);$i++){
            for ($j=0;$j<count($result[$i]['case']);$j++){
                array_push($result_c,[
                    'protein1_peptide'=>$result[$i]['protein1_peptide'],
                    'protein1_peptide_mass'=>$result[$i]['protein1_peptide_mass'],
                    'protein1_ion'=>$result[$i]['protein1_ion'],
                    'protein1_ion_type'=>$result[$i]['protein1_ion_type'],
                    'protein1_ion_mass'=>$result[$i]['protein1_ion_mass'],
                    'protein2_peptide'=>$result[$i]['protein2_peptide'],
                    'protein2_peptide_mass'=>$result[$i]['protein2_peptide_mass'],
                    'protein2_ion'=>$result[$i]['protein2_ion'],
                    'protein2_ion_type'=>$result[$i]['protein2_ion_type'],
                    'protein2_ion_mass'=>$result[$i]['protein2_ion_mass'],
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
        $result=$result_c;
        // considering modification in peptide level (percursor ion / MS1) - target protein
        if ($carbamidomethyl != 'N' || $oxidation != "N"){
            $result_c=[];
            $chk_m_str='/[^';
            if ($carbamidomethyl != 'N'){
                $chk_m_str=$chk_m_str.'C';
            }
            if ($oxidation != 'N'){
                $chk_m_str=$chk_m_str.'M';
            }
            $chk_m_str=$chk_m_str.']/';
            for ($i=0;$i<count($result);$i++){
                $item=[
                    'protein1_peptide'=>$result[$i]['protein1_peptide'],
                    'protein1_peptide_mass'=>$result[$i]['protein1_peptide_mass'],
                    'protein1_ion'=>$result[$i]['protein1_ion'],
                    'protein1_ion_type'=>$result[$i]['protein1_ion_type'],
                    'protein1_ion_mass'=>$result[$i]['protein1_ion_mass'],
                    'protein2_peptide'=>$result[$i]['protein2_peptide'],
                    'protein2_peptide_mass'=>$result[$i]['protein2_peptide_mass'],
                    'protein2_ion'=>$result[$i]['protein2_ion'],
                    'protein2_ion_type'=>$result[$i]['protein2_ion_type'],
                    'protein2_ion_mass'=>$result[$i]['protein2_ion_mass'],
                    'protein1_peptide_c_term_mass'=>$result[$i]['protein1_peptide_c_term_mass'],
                    'protein2_peptide_n_term_mass'=>$result[$i]['protein2_peptide_n_term_mass'],
                    'protein1_peptide_n_term_mass'=>$result[$i]['protein1_peptide_n_term_mass'],
                    'protein2_peptide_c_term_mass'=>$result[$i]['protein2_peptide_c_term_mass'],
                    'peptidecharge'=>$result[$i]['peptidecharge'],
                    'ioncharge'=>$result[$i]['ioncharge'],
                    'combined_score'=>$result[$i]['combined_score']
                ];
                
                $protein1_peptide_str=preg_replace($chk_m_str,"",$result[$i]['protein1_peptide']);
                $protein2_peptide_str=preg_replace($chk_m_str,"",$result[$i]['protein2_peptide']);
                $protein1_ion_str=preg_replace($chk_m_str,"",$result[$i]['protein1_ion']);
                $protein2_ion_str=preg_replace($chk_m_str,"",$result[$i]['protein2_ion']);
                
                $protein1_peptide_c=preg_replace('/[^C]/',"",$protein1_peptide_str);  
                $protein2_peptide_c=preg_replace('/[^C]/',"",$protein2_peptide_str);
                $protein1_ion_c=preg_replace('/[^C]/',"",$protein1_peptide_str);
                $protein2_ion_c=preg_replace('/[^C]/',"",$protein2_peptide_str);

                $protein1_peptide_m=preg_replace('/[^M]/',"",$protein1_peptide_str);
                $protein2_peptide_m=preg_replace('/[^M]/',"",$protein2_peptide_str);
                $protein1_ion_m=preg_replace('/[^M]/',"",$protein1_ion_str);
                $protein2_ion_m=preg_replace('/[^M]/',"",$protein2_ion_str);

                $protein1_peptide_case=modification_all_case($protein1_peptide_str);
                $protein2_peptide_case=modification_all_case($protein2_peptide_str);
                $protein1_ion_case=modification_all_case($protein1_ion_str);
                $protein2_ion_case=modification_all_case($protein2_ion_str);

                $protein1_peptide_case_del_arr=[];
                $protein2_peptide_case_del_arr=[];
                $protein1_ion_case_del_arr=[];
                $protein2_ion_case_del_arr=[];
                for ($j=0;$j<count($protein1_peptide_case);$j++){
                    $del='N';
                    if ($carbamidomethyl=='S'){
                        if ($protein1_peptide_case[$j] != ''){
                            if (strpos($protein1_peptide_case[$j],$protein1_peptide_c) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    if ($oxidation == 'S'){
                        if ($protein1_peptide_case[$j] != ''){
                            if (strpos($protein1_peptide_case[$j],$protein1_peptide_m) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    array_push($protein1_peptide_case_del_arr,$del);
                }
                for ($j=0;$j<count($protein1_peptide_case_del_arr);$j++){
                    if ($protein1_peptide_case_del_arr[$j] == 'Y'){
                        unset($protein1_peptide_case[$j]);
                    }
                }
                $protein1_peptide_case=array_values($protein1_peptide_case);
                // considering modification in peptide level (percursor ion / MS1) - interacted protein(interactome)
                for ($j=0;$j<count($protein2_peptide_case);$j++){
                    $del='N';
                    if ($carbamidomethyl == 'S'){
                        if ($protein2_peptide_case[$j] != ''){
                            if (strpos($protein2_peptide_case[$j],$protein1_peptide_c) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    if ($oxidation == 'S'){
                        if ($protein2_peptide_case[$j] != ''){
                            if (strpos($protein2_peptide_case[$j],$protein2_peptide_m) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    array_push($protein2_peptide_case_del_arr,$del);
                }
                for ($j=0;$j<count($protein2_peptide_case_del_arr);$j++){
                    if ($protein2_peptide_case_del_arr[$j] == 'Y'){
                        unset($protein2_peptide_case[$j]);
                    }
                }
                $protein2_peptide_case=array_values($protein2_peptide_case);
                // considering modification in fragmented ion level (product ion / MS1) - target protein
                for ($j=0;$j<count($protein1_ion_case);$j++){
                    $del='N';
                    if ($carbamidomethyl == 'S'){
                        if ($protein1_ion_case[$j] != ''){
                            if (strpos($protein1_ion_case[$j],$protein1_ion_c) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    if ($oxidation == 'S'){
                        if ($protein1_ion_case[$j] != ''){
                            if (strpos($protein1_ion_case[$j],$protein1_ion_m) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    array_push($protein1_ion_case_del_arr,$del);
                }
                for ($j=0;$j<count($protein1_ion_case_del_arr);$j++){
                    if ($protein1_ion_case_del_arr[$j] == 'Y'){
                        unset($protein1_ion_case[$j]);
                    }
                }
                $protein1_ion_case=array_values($protein1_ion_case);
                // considering modification in fragmented ion level (product ion / MS1) - interacted protein(interactome)
                for ($j=0;$j<count($protein2_ion_case);$j++){
                    $del='N';
                    if ($carbamidomethyl == 'S'){
                        if ($protein2_ion_case[$j] != ''){
                            if (strpos($protein2_ion_case[$j],$protein2_ion_c) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    if ($oxidation == 'S'){
                        if ($protein2_ion_case[$j] != ''){
                            if (strpos($protein2_ion_case[$j],$protein2_ion_m) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    array_push($protein2_ion_case_del_arr,$del);
                }
                for ($j=0;$j<count($protein2_ion_case_del_arr);$j++){
                    if ($protein2_ion_case_del_arr[$j] == 'Y'){
                        unset($protein2_ion_case[$j]);
                    }
                }
                $protein2_ion_case=array_values($protein2_ion_case);
                
                for ($j=0;$j<count($protein1_peptide_case);$j++){
                    $item['protein1_peptide_c_term_mass']=$result[$i]['protein1_peptide_c_term_mass'];
                    $item['protein1_peptide_n_term_mass']=$result[$i]['protein1_peptide_n_term_mass'];
                    $sum_p1p_c=(double)0;
                    $sum_p1p_m=(double)0;
                    $sum_p1p_c=mb_substr_count($protein1_peptide_case[$j],'C')*(double)$modification[0]->mass;
                    $sum_p1p_m=mb_substr_count($protein1_peptide_case[$j],'M')*(double)$modification[1]->mass;
                    $item['protein1_peptide_c_term_mass']=$item['protein1_peptide_c_term_mass']+$sum_p1p_c+$sum_p1p_m;
                    $item['protein1_peptide_n_term_mass']=$item['protein1_peptide_n_term_mass']+$sum_p1p_c+$sum_p1p_m;
                    for ($k=0;$k<count($protein2_peptide_case);$k++){
                        $item['protein2_peptide_c_term_mass']=$result[$i]['protein2_peptide_c_term_mass'];
                        $item['protein2_peptide_n_term_mass']=$result[$i]['protein2_peptide_n_term_mass'];
                        $sum_p2p_c=(double)0;
                        $sum_p2p_m=(double)0;
                        $sum_p2p_c=mb_substr_count($protein2_peptide_case[$k],'C')*(double)$modification[0]->mass;
                        $sum_p2p_m=mb_substr_count($protein2_peptide_case[$k],'M')*(double)$modification[1]->mass;
                        $item['protein2_peptide_c_term_mass']=$item['protein2_peptide_c_term_mass']+$sum_p2p_c+$sum_p2p_m;
                        $item['protein2_peptide_n_term_mass']=$item['protein2_peptide_n_term_mass']+$sum_p2p_c+$sum_p2p_m;
                        for ($l=0;$l<count($protein1_ion_case);$l++){
                            $item['protein1_ion_c_term_mass']=$result[$i]['protein1_ion_c_term_mass'];
                            $item['protein1_ion_n_term_mass']=$result[$i]['protein1_ion_n_term_mass'];
                            $sum_p1i_c=(double)0;
                            $sum_p1i_m=(double)0;
                            $sum_p1i_c=mb_substr_count($protein1_ion_case[$l],'C')*(double)$modification[0]->mass;
                            $sum_p1i_m=mb_substr_count($protein1_ion_case[$l],'M')*(double)$modification[1]->mass;
                            $item['protein1_ion_c_term_mass']=$item['protein1_ion_c_term_mass']+$sum_p1i_c+$sum_p1i_m;
                            $item['protein1_ion_n_term_mass']=$item['protein1_ion_n_term_mass']+$sum_p1i_c+$sum_p1i_m;
                            for ($m=0;$m<count($protein2_ion_case);$m++){
                                $item['protein2_ion_c_term_mass']=$result[$i]['protein2_ion_c_term_mass'];
                                $item['protein2_ion_n_term_mass']=$result[$i]['protein2_ion_n_term_mass'];
                                $sum_p2i_c=(double)0;
                                $sum_p2i_m=(double)0;
                                $sum_p2i_c=mb_substr_count($protein2_ion_case[$m],'C')*(double)$modification[0]->mass;
                                $sum_p2i_m=mb_substr_count($protein2_ion_case[$m],'M')*(double)$modification[1]->mass;
                                $item['protein2_ion_c_term_mass']=$item['protein2_ion_c_term_mass']+$sum_p2i_c+$sum_p2i_m;
                                $item['protein2_ion_n_term_mass']=$item['protein2_ion_n_term_mass']+$sum_p2i_c+$sum_p2i_m;
                                array_push($result_c, $item);
                            }
                        }
                    }
                }
            }
            $result=$result_c;
        }
        // consider charge
        if (count($peptidecharge) >= 1 || count($ioncharge) >= 1){
            $result_c=(array)[];
            for ($i=0;$i<count($result);$i++){
                for ($j=0;$j<count($peptidecharge);$j++){
                    for ($k=0;$k<count($ioncharge);$k++){
                        array_push($result_c, [
                            'combined_score'=>$result[$i]['combined_score'],
                            'protein1_peptide'=>$result[$i]['protein1_peptide'],
                            'protein2_peptide'=>$result[$i]['protein2_peptide'],
                            'peptidecharge'=>$peptidecharge[$j],
                            'protein1_peptide_c_term_mass'=>round($proton+(($result[$i]['protein1_peptide_c_term_mass']+$h2o)/$peptidecharge[$j]),4),
                            'center_mass_peptide_1'=>round($crosslinker_all->mass_center/$peptidecharge[$j],4),
                            'protein2_peptide_n_term_mass'=>round($proton+(($result[$i]['protein2_peptide_n_term_mass']+$h2o)/$peptidecharge[$j]),4),
                            'protein1_peptide_n_term_mass'=>round($proton+(($result[$i]['protein1_peptide_n_term_mass']+$h2o)/$peptidecharge[$j]),4),
                            'center_mass_peptide_2'=>round($crosslinker_all->mass_center/$peptidecharge[$j],4),
                            'protein2_peptide_c_term_mass'=>round($proton+(($result[$i]['protein2_peptide_c_term_mass']+$h2o)/$peptidecharge[$j]),4),
                            'protein1_ion'=>$result[$i]['protein1_ion'],
                            'protein1_ion_type'=>$result[$i]['protein1_ion_type'],
                            'protein2_ion'=>$result[$i]['protein2_ion'],
                            'protein2_ion_type'=>$result[$i]['protein2_ion_type'],
                            'ioncharge'=>$ioncharge[$k],
                            'protein1_ion_c_term_mass'=>round($proton+($result[$i]['protein1_ion_c_term_mass']/$ioncharge[$k]),4),
                            'center_mass_ion_1'=>round($crosslinker_all->mass_center/$ioncharge[$k],4),
                            'protein2_ion_n_term_mass'=>round($proton+($result[$i]['protein2_ion_n_term_mass']/$ioncharge[$k]),4),
                            'protein1_ion_n_term_mass'=>round($proton+($result[$i]['protein1_ion_n_term_mass']/$ioncharge[$k]),4),
                            'center_mass_ion_2'=>round($crosslinker_all->mass_center/$ioncharge[$k],4),
                            'protein2_ion_c_term_mass'=>round($proton+($result[$i]['protein2_ion_c_term_mass']/$ioncharge[$k]),4)
                        ]);
                    }
                }
            }
            $result=$result_c;
        }

        // summarize the result by lists of peptide from Prego
        if ($p1_hp_peptide != '' || $p2_hp_peptide != ''){
            $result_c=[];
            $p1_hp_peptide_arr=explode(' ',$p1_hp_peptide);
            $p2_hp_peptide_arr=explode(' ',$p2_hp_peptide);
            for ($i=0;$i<count($result);$i++){
                $chk_summary='Y';
                if (!in_array($result[$i]['protein1_peptide'], $p1_hp_peptide_arr) ) {
                    $chk_summary='N';
                }
                if (!in_array($result[$i]['protein2_peptide'], $p2_hp_peptide_arr) ) {
                    $chk_summary='N';
                }
                if ($chk_summary == 'Y'){
                    array_push($result_c,$result[$i]);
                }
            }
            $result=$result_c;
        }
        if (!empty($score_min) || !empty($score_max)){
            $result_c=[];
            for ($i=0;$i<count($result);$i++){
                $chk_summary='Y';
                if (!empty($score_min)){
                    if ($result[$i]['combined_score']<$score_min){
                        $chk_summary='N';
                    }
                }                
                if (!empty($score_max)){
                    if ($result[$i]['combined_score']>=$score_max){
                        $chk_summary='N';
                    }
                }
                if ($chk_summary == 'Y'){
                    array_push($result_c,$result[$i]);
                }
            }
            $result=$result_c;
        }
        $result=array_unique($result,SORT_REGULAR);
        $result=array_values($result);

        // pagination
        $page_list=20;
        $page_group=10;
        $page_now=$this->input->post('page_now');
        $page_total=count($result);
        $page_group_total=ceil($page_total/$page_list);
        if (empty($page_now)){
            $page_now=1;
        }
        if ($page_now == 1){
            $page_list_start=0;
        } else{
            $page_list_start=($page_now-1)*$page_list;
        }
        $result=array_slice($result,$page_list_start,$page_list);
        $pagination_count=[];
        $page_group_start=$page_now-$page_group;
        $page_group_end=$page_now+$page_group;
        for ($i=$page_group_start;$i<$page_group_end;$i++){
            if ($i > 1){
                if ($i <$page_group_total){
                    array_push($pagination_count,$i);
                }
            }
        }
        $result_id=1;
        if ($page_now != 1){
            $result_id=1+(($page_now-1)*$page_list);
        }        
        for ($i=0;$i<count($result);$i++){
            $result[$i]['id']=$result_id;
            $result_id=$result_id+1;
        }
        
        // preprocessing combined score to semmarize the result
        if (!empty($score_min)){
            $score_min=$score_min/1000;
        }
        if (!empty($score_max)){
            $score_max=$score_max/1000;
        }

        // keep the information input from search page
        $input_info=[
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
            'carbamidomethyl'=>$carbamidomethyl,
            'oxidation'=>$oxidation,
            'page_now'=>$page_now,
            'page_group_total'=>$page_group_total,
            'pagination_count'=>$pagination_count,
            'p1_hp_peptide'=>$p1_hp_peptide,
            'p2_hp_peptide'=>$p2_hp_peptide
        ];
        
        $data['input_info']=(array)$input_info;
        $data['search_protein']=$protein1_all->name;
        $data['crosslinker']=$crosslinker_all;
        $data['ioncharge']=$ioncharge;
        $data['result']=$result;
        
        // load Result page
        $this->load->view('common/header');
        $this->load->view('search/result',$data);
        $this->load->view('common/footer');
        $end_memory = memory_get_usage();
        
    }

    // load noresult page
    public function noresult(){
        $this->load->view('common/header');
        $this->load->view('search/noresult');
        $this->load->view('common/footer');
    }

    // load nointeraction page
    public function nointeraction(){
        $this->load->view('common/header');
        $this->load->view('search/nointeraction');
        $this->load->view('common/footer');
    }

    // result export as csv (repeat above all code again)
    public function result_csv(){
        $human_protein=$this->input->post('human_protein_reviewed');
        $enzyme=$this->input->post('enzyme');
        $crosslinker=$this->input->post('crosslinker');
        $peptide_length_min=$this->input->post('peptide_length_min');
        $peptide_length_max=$this->input->post('peptide_length_max');
        $ranking=$this->input->post('sorting');
        $peptidecharge=$this->input->post('peptidecharge');
        $ioncharge=$this->input->post('ioncharge');
        $modification=get_modification();
        $data['modification']=$modification;
        $carbamidomethyl=$this->input->post('carbamidomethyl');
        $oxidation=$this->input->post('oxidation');
        $proton=1.00727649;
        $h2o=18.01056468403;

        $score_min=$this->input->post('score_min');
        $score_max=$this->input->post('score_max');
        
        if (!empty($score_min)){
            $score_min=$score_min*1000;
        }
        if (!empty($score_max)){
            $score_max=$score_max*1000;
        }
        
        $p1_hp_peptide=$this->input->post('p1_hp_peptide');
        if ($p1_hp_peptide != ""){
            $p1_hp_peptide=strtoupper($p1_hp_peptide);
        }
        $p2_hp_peptide=$this->input->post('p2_hp_peptide');
        if ($p2_hp_peptide != ""){
            $p2_hp_peptide=strtoupper($p2_hp_peptide);
        }

        $query='select name,string,sequenceID from human_protein_reviewed where entrynumber="'.$human_protein.'"';
        $protein1_all=$this->db->query($query)->result()[0];
        
        if (empty($protein1_all)){
            header("Location:/search/noresult?s=".$human_protein);
            die();
        }
        if ($protein1_all->string == "NaN"){
            header("Location:/search/nointeraction?s=".$human_protein);
            die();
        }

        $query='select * from enzyme where id="'.$enzyme.'"';
        $enzyme_all=$this->db->query($query)->result()[0];
        $query='select name,binding_site,cleavability,mass,mass_c,mass_center,mass_n from crosslinker where id="'.$crosslinker.'"';
        $crosslinker_all=$this->db->query($query)->result()[0];
        $query='select * from amino_acid_mass';
        $aa_mass_all=$this->db->query($query)->result();
        $query='select protein2,combined_score from protein_interaction where protein1="'.$protein1_all->string.'" order by combined_score desc limit '.$ranking;
        $protein2_interaction_all=$this->db->query($query)->result();
        $protein2_string='';

        for ($i=0;$i<count($protein2_interaction_all);$i++){
            if ($i != 0){
                $protein2_string.=',';
            }
            $protein2_string.='"';
            $protein2_string.=$protein2_interaction_all[$i]->protein2;
            $protein2_string.='"';
        }
        
        $query='select name,entrynumber,entryname,string,sequenceID from human_protein_reviewed where string IN ('.$protein2_string.') AND entrynumber not like "%-%"';
        $protein2_all_query=$this->db->query($query)->result();
        
        $protein2_all=[];
        for ($i=0;$i<count($protein2_interaction_all);$i++){
            for ($j=0;$j<count($protein2_all_query);$j++){
                if ($protein2_interaction_all[$i]->protein2==$protein2_all_query[$j]->string){
                    $arr=[];
                    $arr=[
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
        unset($protein2_all_query);
        $interaction_info=[];
        for ($i=0;$i<count($protein2_all);$i++){
            $chk_cs='N';
            if (!empty($score_min)){
                if ($protein2_all[$i]['combined_score']<$score_min){
                    $chk_cs='Y';
                }
            }
            if (!empty($score_max)){
                if ($protein2_all[$i]['combined_score']>=$score_max){
                    $chk_cs='Y';
                }
            }
            if ($chk_cs=='N'){
                for ($j=0;$j<count($protein2_interaction_all);$j++){
                    if ($protein2_all[$i]['string']==$protein2_interaction_all[$j]->protein2){
                        array_push($interaction_info, [
                            'name'=>$protein2_all[$i]['name'],
                            'score'=>$protein2_interaction_all[$j]->combined_score
                        ]);
                    }
                }
            }
        }
        $data['interaction_info']=$interaction_info;
        $sequenceID_arr=seq_digestion($protein1_all->sequenceID,$enzyme_all->cleavage_site,$enzyme_all->exception,$crosslinker_all->binding_site);
        $sequenceID_arr_unset=[];
        for ($i=0;$i<count($sequenceID_arr);$i++){
            if (strlen($sequenceID_arr[$i]['peptide'])>=$peptide_length_min&&strlen($sequenceID_arr[$i]['peptide'])<=$peptide_length_max){
                array_push($sequenceID_arr_unset,['peptide'=>$sequenceID_arr[$i]['peptide']]);
            }
        }
        $sequenceID_arr=$sequenceID_arr_unset;
        unset($sequenceID_arr_unset);
        for ($i=0;$i<count($sequenceID_arr);$i++){
            $peptide_mass=(double)0;
            for ($j=0;$j<strlen($sequenceID_arr[$i]['peptide']);$j++){
                for ($k=0;$k<count($aa_mass_all);$k++){
                    if ($sequenceID_arr[$i]['peptide'][$j]==$aa_mass_all[$k]->slc){
                        $peptide_mass=$peptide_mass+(double)$aa_mass_all[$k]->monoisotopic;
                        if ($j==strlen($sequenceID_arr[$i]['peptide'])-1){
                            $peptide_mass=$peptide_mass;
                        }
                    }
                }
                $sequenceID_arr[$i]['peptide_mass']=$peptide_mass;
            }
           
            $peptide_frag=[];
            for ($j=0;$j<strlen($sequenceID_arr[$i]['peptide']);$j++){
                if ($j>0 && $j<strlen($sequenceID_arr[$i]['peptide'])){
                    $ion=[];
                    $b_ion="";
                    $y_ion="";
                    $b_ion=substr($sequenceID_arr[$i]['peptide'],0,$j);
                    $y_ion=substr($sequenceID_arr[$i]['peptide'],$j);
                    array_push($ion,$b_ion);
                    array_push($ion,$y_ion);
                    $mass=[];
                    $mass_b=(double)0;
                    for ($k=0;$k<strlen($b_ion);$k++){
                        for ($l=0;$l<count($aa_mass_all);$l++){
                            if ($b_ion[$k]==$aa_mass_all[$l]->slc){
                                $mass_b=$mass_b+(double)$aa_mass_all[$l]->monoisotopic;
                            }
                        }
                    }
                    array_push($mass,$mass_b);
                    $mass_y=(double)0;
                    for ($k=0;$k<strlen($y_ion);$k++){
                        for ($l=0;$l<count($aa_mass_all);$l++){
                            if ($y_ion[$k]==$aa_mass_all[$l]->slc){
                                $mass_y=$mass_y+(double)$aa_mass_all[$l]->monoisotopic;
                            }
                        }
                    }
                    array_push($mass,$mass_y);
                    array_push($peptide_frag,['ion'=>$ion, 'ion_mass'=>$mass]);
                }
            }
            $sequenceID_arr[$i]['peptide_frag']=$peptide_frag;
            unset($peptide_frag);
        }
        $protein1_all->sequenceID_arr=$sequenceID_arr;

        for ($i=0;$i<count($protein2_all);$i++){
            $sequenceID_arr=seq_digestion($protein2_all[$i]['sequenceID'],$enzyme_all->cleavage_site,$enzyme_all->exception,$crosslinker_all->binding_site);
            $sequenceID_arr_unset=[];
            for ($j=0;$j<count($sequenceID_arr);$j++){
                if (strlen($sequenceID_arr[$j]['peptide'])>=$peptide_length_min && strlen($sequenceID_arr[$j]['peptide'])<=$peptide_length_max){
                    array_push($sequenceID_arr_unset, ['peptide'=>$sequenceID_arr[$j]['peptide']]);
                }
            }
            $sequenceID_arr=$sequenceID_arr_unset;
            unset($sequenceID_arr_unset);
            for ($j=0;$j<count($sequenceID_arr);$j++){
                $peptide_mass=(double)0;
                for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++){
                    for ($l=0;$l<count($aa_mass_all);$l++){
                        if ($sequenceID_arr[$j]['peptide'][$k]==$aa_mass_all[$l]->slc){
                            $peptide_mass=$peptide_mass+(double)$aa_mass_all[$l]->monoisotopic;
                        }
                    }
                }
                $sequenceID_arr[$j]['peptide_mass']=$peptide_mass;
                
                $peptide_frag=[];
                for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++){
                    if ($k>0 && $k<strlen($sequenceID_arr[$j]['peptide'])){
                        $ion=[];
                        $b_ion='';
                        $y_ion='';
                        $b_ion=substr($sequenceID_arr[$j]['peptide'],0,$k);
                        $y_ion=substr($sequenceID_arr[$j]['peptide'],$k);
                        array_push($ion,$b_ion);
                        array_push($ion,$y_ion);
                        $mass=[];
                        $mass_b=(double)0;
                        for ($l=0;$l<strlen($b_ion);$l++){
                            for ($m=0;$m<count($aa_mass_all);$m++){
                                if ($b_ion[$l]==$aa_mass_all[$m]->slc){
                                    $mass_b=$mass_b+(double)$aa_mass_all[$m]->monoisotopic;
                                }
                            }
                        }
                        array_push($mass,$mass_b);
                        $mass_y=(double)0;
                        for ($l=0;$l<strlen($y_ion);$l++){
                            for ($m=0;$m<count($aa_mass_all);$m++){
                                if ($y_ion[$l]==$aa_mass_all[$m]->slc){
                                    $mass_y=$mass_y+(double)$aa_mass_all[$m]->monoisotopic;
                                }
                            }
                        }
                        array_push($mass,$mass_y);
                        array_push($peptide_frag, ['ion'=>$ion, 'ion_mass'=>$mass]);
                    }
                }
                $sequenceID_arr[$j]['peptide_frag']=$peptide_frag;
                unset($peptide_frag);
            }
            $protein2_all[$i]['sequenceID_arr']=$sequenceID_arr;
        }
        $protein1_result=[];
        for ($i=0;$i<count($protein1_all->sequenceID_arr);$i++){
            $protein1_peptide=$protein1_all->sequenceID_arr[$i]['peptide'];
            $protein1_peptide_mass=$protein1_all->sequenceID_arr[$i]['peptide_mass'];
            for ($j=0;$j<count($protein1_all->sequenceID_arr[$i]['peptide_frag']);$j++){
                for ($k=0;$k<count($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion']);$k++){
                    $protein1_ion=$protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k];
                    $protein1_ion_mass=$protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion_mass'][$k];
                    $protein1_ion_type='';
                    $leng=strlen($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k]);
                    if ($k==0){
                        $protein1_ion_type='b'.$leng;
                    } else{
                        $protein1_ion_type='y'.$leng;
                        $protein1_ion_mass=$protein1_ion_mass+$h2o;
                    }
                    array_push($protein1_result, [
                        'protein1_peptide'=>$protein1_peptide,
                        'protein1_peptide_mass'=>$protein1_peptide_mass,
                        'protein1_ion'=>$protein1_ion,
                        'protein1_ion_type'=>$protein1_ion_type,
                        'protein1_ion_mass'=>$protein1_ion_mass
                    ]);
                }
            }
        }
        $protein2_result=[];
        for ($i=0;$i<count($protein2_all);$i++){
            for ($j=0;$j<count($protein2_all[$i]['sequenceID_arr']);$j++){
                $protein2_peptide=$protein2_all[$i]['sequenceID_arr'][$j]['peptide'];
                $protein2_peptide_mass=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_mass'];
                for ($k=0;$k<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag']);$k++){
                    for ($l=0;$l<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion']);$l++){
                        $protein2_ion=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l];
                        $protein2_ion_mass=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion_mass'][$l];
                        $protein2_ion_type='';
                        $leng=strlen($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l]);
                        if ($l==0){
                            $protein2_ion_type='b'.$leng;
                        } else{
                            $protein2_ion_type='y'.$leng;
                            $protein2_ion_mass=$protein2_ion_mass+$h2o;
                        }
                        array_push($protein2_result,[
                            'protein2_peptide'=>$protein2_peptide,
                            'protein2_peptide_mass'=>$protein2_peptide_mass,
                            'protein2_ion'=>$protein2_ion,
                            'protein2_ion_type'=>$protein2_ion_type,
                            'protein2_ion_mass'=>$protein2_ion_mass,
                            'combined_score'=>$protein2_all[$i]['combined_score']
                        ]);
                    }
                }
            }
        }
        $result=[];
        for ($i=0;$i<count($protein1_result);$i++){
            $protein1_peptide=$protein1_result[$i]['protein1_peptide'];
            $protein1_peptide_mass=$protein1_result[$i]['protein1_peptide_mass'];
            $protein1_ion=$protein1_result[$i]['protein1_ion'];
            $protein1_ion_type=$protein1_result[$i]['protein1_ion_type'];
            $protein1_ion_mass=$protein1_result[$i]['protein1_ion_mass'];
            for ($j=0;$j<count($protein2_result);$j++){
                $protein2_peptide=$protein2_result[$j]['protein2_peptide'];
                $protein2_peptide_mass=$protein2_result[$j]['protein2_peptide_mass'];
                $protein2_ion=$protein2_result[$j]['protein2_ion'];
                $protein2_ion_type=$protein2_result[$j]['protein2_ion_type'];
                $protein2_ion=$protein2_result[$j]['protein2_ion_mass'];
                array_push($result, [
                    'protein1_peptide'=>$protein1_peptide,
                    'protein1_peptide_mass'=>$protein1_peptide_mass,
                    'protein1_ion'=>$protein1_ion,
                    'protein1_ion_type'=>$protein1_ion_type,
                    'protein1_ion_mass'=>$protein1_ion_mass,
                    'protein2_peptide'=>$protein2_peptide,
                    'protein2_peptide_mass'=>$protein2_peptide_mass,
                    'protein2_ion'=>$protein2_ion,
                    'protein2_ion_type'=>$protein2_ion_type,
                    'protein2_ion_mass'=>$protein2_ion_mass,
                    'combined_score'=>$protein2_result[$j]['combined_score']
                ]);
            }
        }
        unset($protein1_result);
        unset($protein2_result);
        unset($protein1_peptide);
        unset($protein1_peptide_mass);
        unset($protein1_ion);
        unset($protein1_ion_type);
        unset($protein1_ion_mass);
        unset($protein2_peptide);
        unset($protein2_peptide_mass);
        unset($protein2_ion);
        unset($protein2_ion_type);
        unset($protein2_ion_mass);

        for ($i=0;$i<count($result);$i++){
            if ($crosslinker_all->cleavability=="Y"){
                $case=mass_case_all(
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_peptide'],
                    $result[$i]['protein2_peptide'],
                    $result[$i]['protein1_peptide_mass'],
                    $result[$i]['protein2_peptide_mass'],
                    $crosslinker_all->mass_c,
                    $crosslinker_all->mass_n
                );
            } else{
                $case=mass_case_all(
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_peptide'],
                    $result[$i]['protein2_peptide'],
                    $result[$i]['protein1_peptide_mass'],
                    $result[$i]['protein2_peptide_mass'],
                    $crosslinker_all->mass,
                    $crosslinker_all->mass
                );
            }
            $result[$i]['case']=$case;
        }
        $result_c=[];
        for ($i=0;$i<count($result);$i++){
            for ($j=0;$j<count($result[$i]['case']);$j++){
                array_push($result_c,[
                    'protein1_peptide'=>$result[$i]['protein1_peptide'],
                    'protein1_peptide_mass'=>$result[$i]['protein1_peptide_mass'],
                    'protein1_ion'=>$result[$i]['protein1_ion'],
                    'protein1_ion_type'=>$result[$i]['protein1_ion_type'],
                    'protein1_ion_mass'=>$result[$i]['protein1_ion_mass'],
                    'protein2_peptide'=>$result[$i]['protein2_peptide'],
                    'protein2_peptide_mass'=>$result[$i]['protein2_peptide_mass'],
                    'protein2_ion'=>$result[$i]['protein2_ion'],
                    'protein2_ion_type'=>$result[$i]['protein2_ion_type'],
                    'protein2_ion_mass'=>$result[$i]['protein2_ion_mass'],
                    'protein1_peptide_c_term_mass'=>$result[$i]['case'][$j]['protein1_c_term_mass'],
                    'protein2_peptide_n_term_mass'=>$result[$i]['case'][$j]['protein2_n_term_mass'],
                    'protein1_peptide_n_term_mass'=>$result[$i]['case'][$j]['protein1_n_term_mass'],
                    'protein2_peptide_c_term_mass'=>$result[$i]['case'][$j]['protein2_c_term_mass'],
                    'combined_score'=>$result[$i]['combined_score']
                ]);
            }
        }
        $result=$result_c;
    
        for ($i=0;$i<count($result);$i++){
            if ($crosslinker_all->cleavability=="Y"){
                $case=mass_case_all(
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_ion'],
                    $result[$i]['protein2_ion'],
                    $result[$i]['protein1_ion_mass'],
                    $result[$i]['protein2_ion_mass'],
                    $crosslinker_all->mass_c,
                    $crosslinker_all->mass_n
                );
            } else{
                $case=mass_case_all(
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_ion'],
                    $result[$i]['protein2_ion'],
                    $result[$i]['protein1_ion_mass'],
                    $result[$i]['protein2_ion_mass'],
                    $crosslinker_all->mass,
                    $crosslinker_all->mass
                );
            }
            $result[$i]['case']=$case;
        }
        
        $result_c=[];
        for ($i=0;$i<count($result);$i++){
            for ($j=0;$j<count($result[$i]['case']);$j++){
                array_push($result_c,[
                    'protein1_peptide'=>$result[$i]['protein1_peptide'],
                    'protein1_peptide_mass'=>$result[$i]['protein1_peptide_mass'],
                    'protein1_ion'=>$result[$i]['protein1_ion'],
                    'protein1_ion_type'=>$result[$i]['protein1_ion_type'],
                    'protein1_ion_mass'=>$result[$i]['protein1_ion_mass'],
                    'protein2_peptide'=>$result[$i]['protein2_peptide'],
                    'protein2_peptide_mass'=>$result[$i]['protein2_peptide_mass'],
                    'protein2_ion'=>$result[$i]['protein2_ion'],
                    'protein2_ion_type'=>$result[$i]['protein2_ion_type'],
                    'protein2_ion_mass'=>$result[$i]['protein2_ion_mass'],
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
        $result=$result_c;
        if ($carbamidomethyl != 'N' || $oxidation != "N"){
            $result_c=[];
            $chk_m_str='/[^';
            if ($carbamidomethyl != 'N'){
                $chk_m_str=$chk_m_str.'C';
            }
            if ($oxidation != 'N'){
                $chk_m_str=$chk_m_str.'M';
            }
            $chk_m_str=$chk_m_str.']/';
            for ($i=0;$i<count($result);$i++){
                $item=[
                    'protein1_peptide'=>$result[$i]['protein1_peptide'],
                    'protein1_peptide_mass'=>$result[$i]['protein1_peptide_mass'],
                    'protein1_ion'=>$result[$i]['protein1_ion'],
                    'protein1_ion_type'=>$result[$i]['protein1_ion_type'],
                    'protein1_ion_mass'=>$result[$i]['protein1_ion_mass'],
                    'protein2_peptide'=>$result[$i]['protein2_peptide'],
                    'protein2_peptide_mass'=>$result[$i]['protein2_peptide_mass'],
                    'protein2_ion'=>$result[$i]['protein2_ion'],
                    'protein2_ion_type'=>$result[$i]['protein2_ion_type'],
                    'protein2_ion_mass'=>$result[$i]['protein2_ion_mass'],
                    'protein1_peptide_c_term_mass'=>$result[$i]['protein1_peptide_c_term_mass'],
                    'protein2_peptide_n_term_mass'=>$result[$i]['protein2_peptide_n_term_mass'],
                    'protein1_peptide_n_term_mass'=>$result[$i]['protein1_peptide_n_term_mass'],
                    'protein2_peptide_c_term_mass'=>$result[$i]['protein2_peptide_c_term_mass'],
                    'peptidecharge'=>$result[$i]['peptidecharge'],
                    'ioncharge'=>$result[$i]['ioncharge'],
                    'combined_score'=>$result[$i]['combined_score']
                ];
                
                $protein1_peptide_str=preg_replace($chk_m_str,"",$result[$i]['protein1_peptide']);
                $protein2_peptide_str=preg_replace($chk_m_str,"",$result[$i]['protein2_peptide']);
                $protein1_ion_str=preg_replace($chk_m_str,"",$result[$i]['protein1_ion']);
                $protein2_ion_str=preg_replace($chk_m_str,"",$result[$i]['protein2_ion']);
                
                $protein1_peptide_c=preg_replace('/[^C]/',"",$protein1_peptide_str); 
                $protein2_peptide_c=preg_replace('/[^C]/',"",$protein2_peptide_str);
                $protein1_ion_c=preg_replace('/[^C]/',"",$protein1_peptide_str);
                $protein2_ion_c=preg_replace('/[^C]/',"",$protein2_peptide_str);
               
                $protein1_peptide_m=preg_replace('/[^M]/',"",$protein1_peptide_str);
                $protein2_peptide_m=preg_replace('/[^M]/',"",$protein2_peptide_str);
                $protein1_ion_m=preg_replace('/[^M]/',"",$protein1_ion_str);
                $protein2_ion_m=preg_replace('/[^M]/',"",$protein2_ion_str);
                
                $protein1_peptide_case=modification_all_case($protein1_peptide_str);
                $protein2_peptide_case=modification_all_case($protein2_peptide_str);
                $protein1_ion_case=modification_all_case($protein1_ion_str);
                $protein2_ion_case=modification_all_case($protein2_ion_str);
                
                $protein1_peptide_case_del_arr=[];
                $protein2_peptide_case_del_arr=[];
                $protein1_ion_case_del_arr=[];
                $protein2_ion_case_del_arr=[];
                for ($j=0;$j<count($protein1_peptide_case);$j++){
                    $del='N';
                    if ($carbamidomethyl=='S'){
                        if ($protein1_peptide_case[$j] != ''){
                            if (strpos($protein1_peptide_case[$j],$protein1_peptide_c) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    if ($oxidation == 'S'){
                        if ($protein1_peptide_case[$j] != ''){
                            if (strpos($protein1_peptide_case[$j],$protein1_peptide_m) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    array_push($protein1_peptide_case_del_arr,$del);
                }
                for ($j=0;$j<count($protein1_peptide_case_del_arr);$j++){
                    if ($protein1_peptide_case_del_arr[$j] == 'Y'){
                        unset($protein1_peptide_case[$j]);
                    }
                }
                $protein1_peptide_case=array_values($protein1_peptide_case);
                
                for ($j=0;$j<count($protein2_peptide_case);$j++){
                    $del='N';
                    if ($carbamidomethyl == 'S'){
                        if ($protein2_peptide_case[$j] != ''){
                            if (strpos($protein2_peptide_case[$j],$protein1_peptide_c) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    if ($oxidation == 'S'){
                        if ($protein2_peptide_case[$j] != ''){
                            if (strpos($protein2_peptide_case[$j],$protein2_peptide_m) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    array_push($protein2_peptide_case_del_arr,$del);
                }
                for ($j=0;$j<count($protein2_peptide_case_del_arr);$j++){
                    if ($protein2_peptide_case_del_arr[$j] == 'Y'){
                        unset($protein2_peptide_case[$j]);
                    }
                }
                $protein2_peptide_case=array_values($protein2_peptide_case);
                
                for ($j=0;$j<count($protein1_ion_case);$j++){
                    $del='N';
                    if ($carbamidomethyl == 'S'){
                        if ($protein1_ion_case[$j] != ''){
                            if (strpos($protein1_ion_case[$j],$protein1_ion_c) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    if ($oxidation == 'S'){
                        if ($protein1_ion_case[$j] != ''){
                            if (strpos($protein1_ion_case[$j],$protein1_ion_m) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    array_push($protein1_ion_case_del_arr,$del);
                }
                for ($j=0;$j<count($protein1_ion_case_del_arr);$j++){
                    if ($protein1_ion_case_del_arr[$j] == 'Y'){
                        unset($protein1_ion_case[$j]);
                    }
                }
                $protein1_ion_case=array_values($protein1_ion_case);
                
                for ($j=0;$j<count($protein2_ion_case);$j++){
                    $del='N';
                    if ($carbamidomethyl == 'S'){
                        if ($protein2_ion_case[$j] != ''){
                            if (strpos($protein2_ion_case[$j],$protein2_ion_c) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    if ($oxidation == 'S'){
                        if ($protein2_ion_case[$j] != ''){
                            if (strpos($protein2_ion_case[$j],$protein2_ion_m) !== FALSE){
                            } else {
                                $del='Y';
                            }
                        }
                    }
                    array_push($protein2_ion_case_del_arr,$del);
                }
                for ($j=0;$j<count($protein2_ion_case_del_arr);$j++){
                    if ($protein2_ion_case_del_arr[$j] == 'Y'){
                        unset($protein2_ion_case[$j]);
                    }
                }
                $protein2_ion_case=array_values($protein2_ion_case);
                
                for ($j=0;$j<count($protein1_peptide_case);$j++){
                    $item['protein1_peptide_c_term_mass']=$result[$i]['protein1_peptide_c_term_mass'];
                    $item['protein1_peptide_n_term_mass']=$result[$i]['protein1_peptide_n_term_mass'];
                    $sum_p1p_c=(double)0;
                    $sum_p1p_m=(double)0;
                    $sum_p1p_c=mb_substr_count($protein1_peptide_case[$j],'C')*(double)$modification[0]->mass;
                    $sum_p1p_m=mb_substr_count($protein1_peptide_case[$j],'M')*(double)$modification[1]->mass;
                    $item['protein1_peptide_c_term_mass']=$item['protein1_peptide_c_term_mass']+$sum_p1p_c+$sum_p1p_m;
                    $item['protein1_peptide_n_term_mass']=$item['protein1_peptide_n_term_mass']+$sum_p1p_c+$sum_p1p_m;
                    for ($k=0;$k<count($protein2_peptide_case);$k++){
                        $item['protein2_peptide_c_term_mass']=$result[$i]['protein2_peptide_c_term_mass'];
                        $item['protein2_peptide_n_term_mass']=$result[$i]['protein2_peptide_n_term_mass'];
                        $sum_p2p_c=(double)0;
                        $sum_p2p_m=(double)0;
                        $sum_p2p_c=mb_substr_count($protein2_peptide_case[$k],'C')*(double)$modification[0]->mass;
                        $sum_p2p_m=mb_substr_count($protein2_peptide_case[$k],'M')*(double)$modification[1]->mass;
                        $item['protein2_peptide_c_term_mass']=$item['protein2_peptide_c_term_mass']+$sum_p2p_c+$sum_p2p_m;
                        $item['protein2_peptide_n_term_mass']=$item['protein2_peptide_n_term_mass']+$sum_p2p_c+$sum_p2p_m;
                        for ($l=0;$l<count($protein1_ion_case);$l++){
                            $item['protein1_ion_c_term_mass']=$result[$i]['protein1_ion_c_term_mass'];
                            $item['protein1_ion_n_term_mass']=$result[$i]['protein1_ion_n_term_mass'];
                            $sum_p1i_c=(double)0;
                            $sum_p1i_m=(double)0;
                            $sum_p1i_c=mb_substr_count($protein1_ion_case[$l],'C')*(double)$modification[0]->mass;
                            $sum_p1i_m=mb_substr_count($protein1_ion_case[$l],'M')*(double)$modification[1]->mass;
                            $item['protein1_ion_c_term_mass']=$item['protein1_ion_c_term_mass']+$sum_p1i_c+$sum_p1i_m;
                            $item['protein1_ion_n_term_mass']=$item['protein1_ion_n_term_mass']+$sum_p1i_c+$sum_p1i_m;
                            for ($m=0;$m<count($protein2_ion_case);$m++){
                                $item['protein2_ion_c_term_mass']=$result[$i]['protein2_ion_c_term_mass'];
                                $item['protein2_ion_n_term_mass']=$result[$i]['protein2_ion_n_term_mass'];
                                $sum_p2i_c=(double)0;
                                $sum_p2i_m=(double)0;
                                $sum_p2i_c=mb_substr_count($protein2_ion_case[$m],'C')*(double)$modification[0]->mass;
                                $sum_p2i_m=mb_substr_count($protein2_ion_case[$m],'M')*(double)$modification[1]->mass;
                                $item['protein2_ion_c_term_mass']=$item['protein2_ion_c_term_mass']+$sum_p2i_c+$sum_p2i_m;
                                $item['protein2_ion_n_term_mass']=$item['protein2_ion_n_term_mass']+$sum_p2i_c+$sum_p2i_m;
                                array_push($result_c, $item);
                            }
                        }
                    }
                }
            }
            $result=$result_c;
        }
        
        if (count($peptidecharge) >= 1 || count($ioncharge) >= 1){
            $result_c=(array)[];
            for ($i=0;$i<count($result);$i++){
                for ($j=0;$j<count($peptidecharge);$j++){
                    for ($k=0;$k<count($ioncharge);$k++){
                        array_push($result_c, [
                            'combined_score'=>$result[$i]['combined_score']/1000,
                            'protein1_peptide'=>$result[$i]['protein1_peptide'],
                            'protein2_peptide'=>$result[$i]['protein2_peptide'],
                            'peptidecharge'=>$peptidecharge[$j],
                            'protein1_peptide_c_term_mass'=>round($proton+(($result[$i]['protein1_peptide_c_term_mass']+$h2o)/$peptidecharge[$j]),4),
                            'center_mass_peptide_1'=>round($crosslinker_all->mass_center/$peptidecharge[$j],4),
                            'protein2_peptide_n_term_mass'=>round($proton+(($result[$i]['protein2_peptide_n_term_mass']+$h2o)/$peptidecharge[$j]),4),
                            'protein1_peptide_n_term_mass'=>round($proton+(($result[$i]['protein1_peptide_n_term_mass']+$h2o)/$peptidecharge[$j]),4),
                            'center_mass_peptide_2'=>round($crosslinker_all->mass_center/$peptidecharge[$j],4),
                            'protein2_peptide_c_term_mass'=>round($proton+(($result[$i]['protein2_peptide_c_term_mass']+$h2o)/$peptidecharge[$j]),4),
                            'protein1_ion'=>$result[$i]['protein1_ion'],
                            'protein1_ion_type'=>$result[$i]['protein1_ion_type'],
                            'protein2_ion'=>$result[$i]['protein2_ion'],
                            'protein2_ion_type'=>$result[$i]['protein2_ion_type'],
                            'ioncharge'=>$ioncharge[$k],
                            'protein1_ion_c_term_mass'=>round($proton+($result[$i]['protein1_ion_c_term_mass']/$ioncharge[$k]),4),
                            'center_mass_ion_1'=>round($crosslinker_all->mass_center/$ioncharge[$k],4),
                            'protein2_ion_n_term_mass'=>round($proton+($result[$i]['protein2_ion_n_term_mass']/$ioncharge[$k]),4),
                            'protein1_ion_n_term_mass'=>round($proton+($result[$i]['protein1_ion_n_term_mass']/$ioncharge[$k]),4),
                            'center_mass_ion_2'=>round($crosslinker_all->mass_center/$ioncharge[$k],4),
                            'protein2_ion_c_term_mass'=>round($proton+($result[$i]['protein2_ion_c_term_mass']/$ioncharge[$k]),4)
                        ]);
                    }
                }
            }
            $result=$result_c;
        }
        if ($p1_hp_peptide != '' || $p2_hp_peptide != ''){
            $result_c=[];
            $p1_hp_peptide_arr=explode(' ',$p1_hp_peptide);
            $p2_hp_peptide_arr=explode(' ',$p2_hp_peptide);
            for ($i=0;$i<count($result);$i++){
                $chk_summary='Y';
                if (!in_array($result[$i]['protein1_peptide'], $p1_hp_peptide_arr) ) {
                    $chk_summary='N';
                }
                if (!in_array($result[$i]['protein2_peptide'], $p2_hp_peptide_arr) ) {
                    $chk_summary='N';
                }
                if ($chk_summary == 'Y'){
                    array_push($result_c,$result[$i]);
                }
            }
            $result=$result_c;
        }
        if (!empty($score_min) || !empty($score_max)){
            $result_c=[];
            for ($i=0;$i<count($result);$i++){
                $chk_summary='Y';
                if (!empty($score_min)){
                    if ($result[$i]['combined_score']<$score_min){
                        $chk_summary='N';
                    }
                }                
                if (!empty($score_max)){
                    if ($result[$i]['combined_score']>=$score_max){
                        $chk_summary='N';
                    }
                }
                if ($chk_summary == 'Y'){
                    array_push($result_c,$result[$i]);
                }
            }
            $result=$result_c;
        }
        $result=array_unique($result,SORT_REGULAR);
        $result=array_values($result);


        // csv export
        $filename="PPIAT_".$protein1_all->name."_".date("Y-m-d").".csv";
        header('Content-Type:text/csv; charset=utf-8');
        header('Content-Disposition:attachment; filename='.$filename);
        $handle=fopen('php://output','w');
        $columns_description=array(
            array("Column Description"),
            array("Columns Title"=>"","Descriptions"=>""),
            array("Columns Title"=>"Colum Name","Descriptions"=>"Description"),
            array("Column Title"=>"Score","Description"=>"Probability score which protein interaction with target protein"),
            array("Column Title"=>"Protein A' Peptide", "Description"=>"Peptide sequence after digested protein A by enzyme"),
            array("Column Title"=>"Cross linker","Description"=>"Cross Linker which want to use for XL-MS"),
            array("Column Title"=>"Protein B' Peptide", "Description"=>"Peptide sequence after digested protein B by enzyme"),
            array("Column Title"=>"Precursor Charge", "Description"=>"Charge values on MS1"),
            array("Column Title"=>"Precursor m/z (Mass A)", "Description"=>"Mass values for Protein A linked with one side of XL"),
            array("Column Title"=>"Precursor m/z (Mass B)", "Description"=>"Mass value for XL after cleavage by collision energy on MS2"),
            array("Column Title"=>"Precursor m/z (Mass C)", "Description"=>"Mass value for Protein B linked with the other side of XL"),
            array("Column Title"=>"Precursor m/z (Mass D)", "Description"=>"Mass value for Protein A linked with the other side of XL"),
            array("Column Title"=>"Precursor m/z (Mass E)", "Description"=>"Mass value for XL after cleavage by collision energy on MS2"),
            array("Column Title"=>"Precursor m/z (Mass F)", "Description"=>"Mass value for Protein B linked with one side of XL"),
            array("Column Title"=>"Protein A' Ion","Description"=>"Ion sequence after fragmented Protein A by collision energy on MS2"),
            array("Column Title"=>"Protein A' Ion type","Description"=>"Type of Protein A' Ion"),
            array("Column Title"=>"Protein B' Ion","Description"=>"Ion sequence after fragmented Protein B by collision energy on MS2"),
            array("Column Title"=>"Protein B' Ion type","Description"=>"Type of Protein B' ion"),
            array("Column Title"=>"Product Charge","Description"=>"Charge values on MS3"),
            array("Column Title"=>"Product m/z (Mass A)","Description"=>"Mass value for Protein A Ion linked with one side of XL"),
            array("Column Title"=>"Product m/z (Mass B)","Description"=>"Mass value for XL after cleavage by collision energy on MS2"),
            array("Column Title"=>"Product m/z (Mass C)","Description"=>"Mass value for Protein B Ion linked with the other side of XL"),
            array("Column Title"=>"Product m/z (Mass D)","Description"=>"Mass value for Protein A Ion linked with the other side of XL"),
            array("Column Title"=>"Product m/z (Mass E)","Description"=>"Mass value for XL after cleavage by collision energy on MS2"),
            array("Column Title"=>"Product m/z (Mass F)","Description"=>"Mass value for Protein B Ion linked with one side of XL"),
            array("columns Title"=>"","Descriptions" => ""),
            array("Column Title"=>"* if in case of Mass A is linked with the right side of XL, in case Mass C is linked with the left side of XL"),
            array("Column Title"=>"* Mass B is cleavaged XL mass after MS2 step"),
            array("columns Title"=>"","Descriptions"=>""),
            array("columns Title"=>"","Descriptions"=>"")
        );
        $columns=array(
            "Score","Protein A' Peptide","Protein B' Peptide","Precursor Charge","Precursor m/z (Mass A)","Precursor m/z (Mass B)","Precursor m/z (Mass C)","Precursor m/z (Mass D)","Precursor m/z (Mass E)","Precursor m/z (Mass F)",
            "Protein A' Ion","Protein A' Ion type","Protein B' ion","Protein B' ion type","Product Charge","Product m/z (Mass A)","Product m/z (Mass B)","Product m/z (Mass C)","Product m/z (Mass D)","Product m/z (Mass E)","Product m/z (Mass F)"
        );
        foreach ($columns_description as $field){
            fputcsv($handle,$field);
        }
        fputcsv($handle,$columns);
        foreach ($result as $fields){
            fputcsv($handle,$fields);
        }
        fclose($handle);
    }
}?>