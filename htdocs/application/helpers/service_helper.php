<?php
defined('BASEPATH') OR exit('No direct script access allowed');


# Protein digestion as enzyme
if ( ! function_exists('seq_digestion'))
{   
    function seq_digestion($seq='',$cut='',$nocut='')  // 함수 인자로는 sequence, enzyme cleavage site, exception 을 사용
    {
        $seq = str_replace(' ','',$seq); // 문자열 치환을 통한 공백 제거(실제 문자열 형태)

        $return = [];   // 최종 리턴 배열을 담을 빈 어레이 생성
        $seq_arr = str_split($seq);   // sequence를 split 하여 각 AA 로 구성된 어레이로 변환 
        $item = '';   // 최종 배열에 들어갈 하나의 item 문자열 초기화

        for ($i=0;$i<count($seq_arr);$i++) {// $seq_arr 전체 배열 길이만큼 반복
            if (strpos($nocut, $seq_arr[$i]) !== FALSE) { // $seq_arr 현재 문자가 자르면 안되는 문자열에 포함되어 있으면
                if (strpos($cut, $seq_arr[$i-1]) !== FALSE) { // $seq_arr 현재 문자 이전 문자가 자르는 문자열에 포함되어 있으면 처리 안함 (조건에 맞는 값이 들어올 떄 액션이 비어있기 때문에 if 문을 탈출 함)
                } else { // $seq_arr 현재 문자 이전 문자가 자르는 문자열에 포함되어 있지 않으면
                    $item = $item.$seq_arr[$i];// 자르지 않는 문자열 이전 item 문자열에 추가
                }
            } else {// $seq_arr 현재 문자가 자르면 안되는 문자열에 포함되어 있지 않으면 실행(포함되어 있으면 이전에 처리)
                if ($i+1 != count($seq_arr)) {// $seq_arr 현재 문자가 마지막 배열이 아니면
                    if (strpos($cut, $seq_arr[$i]) !== FALSE) {// $seq_arr 현재 문자가 $cut 문자열에 포함 되어 있으면
                        if (strpos($nocut, $seq_arr[$i+1]) !== FALSE) {// $seq_arr 현재 문자 다음 문자가 nocut 문자열에 포함 되어 있으면
                            $item = $item.$seq_arr[$i].$seq_arr[$i+1];// 이전 누적 item 문자열에 현재 문자열, 다음 nocut 문자 추가
                        } else {// $seq_arr 현재 문자 다음 문자가 nocut 문자열에 포함 되어 있지 않으면
                            $item = $item.$seq_arr[$i];// 이전 누적 item 문자열에 현재 문자열 추가
                            array_push($return, ['peptide'=>$item]);
                            
                            $item = '';// 최종 배열에 들어갈 item 문자열 초기화
                        }
                    } else {// $seq_arr 현재 문자가 $cut 문자열에 포함 되어 있지 않으면
                        $item = $item.$seq_arr[$i];// 이전 누적 item 문자열에 현재 문자열 추가
                    }   
                } else {// $seq_arr 현재 문자가 $cut 문자열에 포함 되어 있지 않으면
                    $item = $item.$seq_arr[$i];// 이전 누적 item 문자열에 현재 문자열 추가
                }
            }
        }

        array_push($return, ['peptide'=>$item]);
      
        // 최종 리턴
        return $return;
    }
}


# peptide / ion fragment 별 cl 이 결합되는 경우의 수 계산
if (!function_exists('mass_case_all')) {
    function mass_case_all($check='', $seq1='', $seq2='', $mass1='', $mass2='', $mass_c=0, $mass_n=0) {   // 사용 인자로 cl binding site, protein1 peptide / ion frag seq, protein2 / ion frag peptide seq, protein1 / ion frag amss, protein2 / ion frag mass, cl mass c, cl mass n 사용 
        $return = [];   // 최종 리턴값을 담을 어레이

        $seq1_arr = str_split($seq1);   // protein1 seq / ion frag 를 split 하여 각 AA 로 변환
        $seq2_arr = str_split($seq2);   // protein2 seq / ion frag 를 split 하여 각 AA 로 변환
        $count1 = (int) 1;   // protein1 seq / ion frag 초기 count 값을 1로 설정 (sequence에 cl binding site 가 없더라도 binding 하지 않는 경우는 항상 존재하기 때문)
        $count2 = (int) 1;   // protein2 seq / ion frag 초기 count 값을 1로 설정 (sequence에 cl binding site 가 없더라도 binding 하지 않는 경우는 항상 존재하기 때문)

        for ($i=0;$i<count($seq1_arr);$i++) {   // protein1 seq / ion frag 를 split 한 각 AA 의 개수만큼 반복 
            if (strpos($check, $seq1_arr[$i]) !== FALSE) {   // 특정 문자열(cl binding site) 가 protein1 seq / ion frag 를 split 하여 각 AA 에 포함되어 있으면 (일치하면) count1 씩 1 증가
                $count1 = $count1 + 1;
            }
        }
        for ($i=0;$i<count($seq2_arr);$i++) {
            if (strpos($check, $seq2_arr[$i]) !== FALSE) {   // 특정 문자열(cl binding site) 가 protein2 seq / ion frag 를 split 하여 각 AA 에 포함되어 있으면 (일치하면) count1 씩 1 증가
                $count2 = $count2 + 1;
            }
        }

        for ($i=0;$i<$count1;$i++) {   // protein1 seq / ion frag 를 split 하여 각 AA 에 포함된 cl binding site 개수만큼 반복
            for ($j=0;$j<$count2;$j++) {   // protein2 seq / ion frag 를 split 하여 각 AA 에 포함된 cl binding site 개수만큼 반복 (protein1 peptide, protein1 ion frag 와 protein2 peptide, protein2 ion frag 의 경우의 수)
                $protein1_c_term_mass = (double) $mass1 + ((double) $mass_c * $i);   // protein1 의 sequence 질량값과 cl c term 질량값을 더함
                $protein2_n_term_mass = (double) $mass2 + ((double) $mass_n * $j);   // protein2 의 sequence 질량값과 cl n term 질량값을 더함
                $protein1_n_term_mass = (double) $mass1 + ((double) $mass_n * $i);   // protein1 의 sequence 질량값과 cl n term 질량값을 더함
                $protein2_c_term_mass = (double) $mass2 + ((double) $mass_c * $j);   // protein2 의 sequence 질량값과 cl c term 질량값을 더함
                array_push($return, [
                    'protein1_c_term_mass'=>$protein1_c_term_mass,   // 최종 return 배열에 protein1_c_term_mass 컬럼을 생성 후 값 추가
                    'protein2_n_term_mass'=>$protein2_n_term_mass,   // 최종 return 배열에 protein2_n_term_mass 컬럼을 생성 후 값 추가
                    'protein1_n_term_mass'=>$protein1_n_term_mass,   // 최종 return 배열에 protein1_n_term_mass 컬럼을 생성 후 값 추가
                    'protein2_c_term_mass'=>$protein2_c_term_mass    // 최종 return 배열에 protein2_c_term_mass 컬럼을 생성 후 값 추가
                ]);
            }
        }  
        return $return;   // 최종 return 배열 출력
    }
}
?>