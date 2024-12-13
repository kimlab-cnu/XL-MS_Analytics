<?php defined('BASEPATH') OR exit('No direct script access allowed');
// digestion logic (accept miss cleavage if cross linker bind with the site of digest by enzyme)
if (! function_exists('seq_digestion')){
    function seq_digestion($seq='',$cut='',$nocut='',$bs=''){
        $seq=str_replace(' ','',$seq);
        $return=[];
        $seq_arr=str_split($seq);
        $item='';
        $miss_count=1;
        for ($i=0;$i<count($seq_arr);$i++){
            if (strpos($nocut,$seq_arr[$i]) !== FALSE){
                if (strpos($cut,$seq_arr[$i-1]) !== FALSE){
                } else {
                    $item=$item.$seq_arr[$i];
                }
            } else {
                if ($i+1 != count($seq_arr)){
                    if (strpos($cut,$seq_arr[$i]) !== FALSE){
                        if (strpos($bs,$seq_arr[$i]) !== FALSE){
                            if ($miss_count == 1){
                                $item=$item.$seq_arr[$i];
                                $miss_count=0;
                            } else {
                                if (strpos($nocut,$seq_arr[$i+1]) !== FALSE){
                                    $item=$item.$seq_arr[$i].$seq_arr[$i+1];
                                } else {
                                    $item=$item.$seq_arr[$i];
                                    array_push($return, ['peptide'=>$item]);
                                    $item='';
                                    $miss_count=1;
                                }
                            }
                        } else {
                            if (strpos($nocut,$seq_arr[$i+1]) !== FALSE){
                                $item=$item.$seq_arr[$i].$seq_arr[$i+1];
                            } else {
                                $item=$item.$seq_arr[$i];
                                array_push($return, ['peptide'=>$item]);
                                $item='';
                                $miss_count=1;
                            }
                        }
                    } else {
                        $item=$item.$seq_arr[$i];
                    }
                } else {
                    $item=$item.$seq_arr[$i];
                }
            }
        }
        array_push($return, ['peptide'=>$item]);
        return $return;
    }
}

// calculate all cases of cross linking in peptide(precursor / MS1) and freamented ion(product / MS2) level 
if (!function_exists('mass_case_all')){
    function mass_case_all($check='',$seq1='',$seq2='',$mass1='',$mass2='',$mass_c=0,$mass_n=0){
        $return=[];
        $seq1_arr=str_split($seq1);
        $seq2_arr=str_split($seq2);
        $count1=(int)1;
        $count2=(int)1;
        for ($i=0;$i<count($seq1_arr);$i++){
            if (strpos($check,$seq1_arr[$i]) !== FALSE){
                $count1=$count1+1;
            }
        }
        for ($i=0;$i<count($seq2_arr);$i++){
            if (strpos($check,$seq2_arr[$i]) !== FALSE){
                $count2=$count2+1;
            }
        }
        for ($i=0;$i<$count1;$i++){
            for ($j=0;$j<$count2;$j++){
                $protein1_c_term_mass=(double)$mass1+((double)$mass_c*$i);
                $protein2_n_term_mass=(double)$mass2+((double)$mass_n*$j);
                $protein1_n_term_mass=(double)$mass1+((double)$mass_n*$i);
                $protein2_c_term_mass=(double)$mass2+((double)$mass_c*$j);
                array_push($return, [
                    'protein1_c_term_mass'=>$protein1_c_term_mass,
                    'protein2_n_term_mass'=>$protein2_n_term_mass,
                    'protein1_n_term_mass'=>$protein1_n_term_mass,
                    'protein2_c_term_mass'=>$protein2_c_term_mass
                ]);
            }
        }
        return $return;
    }
}

if (!function_exists('get_modification')){
    function get_modification(){
        $return=[];
        $ci=&get_instance();
        $ci->load->database();
        $return=$ci->db->query('select id,name,str,mass from modification')->result();
        return $return;
    }
}

// consider all case of modification (static and variable)
function modification_all_case($array){
    $array=str_split($array);
    $results=array(array());
    foreach ($array as $element)
    foreach ($results as $combination)
    array_push($results, array_merge(array($element),$combination));
    $results_c=[];
    for ($i=0;$i<count($results);$i++){
        $str='';
        for ($j=0;$j<count($results[$i]);$j++){
            $str=$results[$i][$j].$str;
        }
        array_push($results_c,$str);
    }
    return $results_c;
}?>