<?php ob_start();
defined('BASEPATH') OR exit('No direct script access allowed');
class search extends CI_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->database();// 데이터 베이스 연결
    }
    public function index(){
        $data['enzyme']=$this->db->query('select id,name from enzyme')->result();
        $data['crosslinker']=$this->db->query('select id,name from crosslinker')->result();
        $modification=get_modification();
        $data['modification']=$modification;
        // 검색페이지 프론트엔드 구성
        $this->load->view('common/header');
        $this->load->view('search/search',$data);
        $this->load->view('common/footer');
    }
    public function result(){
        $start_memory=memory_get_usage();// 코드 시작시 사용 메모리 측정
        // 1. Search 페이지에서 검색을 위한 입력 정보 저장
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
        $proton=1.00727649;// protin 질량값
        $h2o=18.01056468403;// h2o 질량값
        // 1-1. Result 페이지에서 결과 요약을 위한 입력 정보 저장 (Combined Score 값을 통한 결과 요약)
        $score_min=$this->input->post('score_min');// 결과페이지에서 결과 요약을 위한 상호작용하는 확률 score값 범위
        $score_max=$this->input->post('score_max');// 결과페이지에서 결과 요약을 위한 상호작용하는 확률 score값 범위
        // 1-2. 1-1에서 입력된 결과 요약을 위한 정보가 입력되면 해당 값 실수 (수수점 확률값)에서 정수(0~999 사이의 값)로 변환
        if (!empty($score_min)){// 상호작용하는 확률 score값이 입력되면
            $score_min=$score_min*1000;// 확률 score값에 1000을 곱함 (확률값은 소수점자리이며, DB에서는 1~1000점으로 분류하기 때문)
        }
        if (!empty($score_max)){// 상호작용하는 확률 score값이 입력되면
            $score_max=$score_max*1000;// 확률 score값에 1000을 곱함 (확률값은 소수점자리이며, DB에서는 1~1000점으로 분류하기 때문)
        }
        // 1-3. Result 페이지에서 결과요약을 위한 입력 정보 저장 (Prego 결과를 통한 결과 요약)
        $p1_hp_peptide=$this->input->post('p1_hp_peptide');// prego을 통한 타겟 단백질의 peptide sequence 값을 입력
        if ($p1_hp_peptide != ""){// 타겟 단백질의 peptide sequence 값이 입력되면
            $p1_hp_peptide=strtoupper($p1_hp_peptide);// sequence 갑들을 대문자로 변환
        }
        $p2_hp_peptide=$this->input->post('p2_hp_peptide');// prego를 통한 타겟 단백질과 상호작용하는 단백질들의 peptide sequence 값을 입력
        if ($p2_hp_peptide != ""){// 타겟 단백질과 상호작용하는 단백질들의 peptide sequence 값이 입력되면
            $p2_hp_peptide=strtoupper($p2_hp_peptide);// sequence 값들을 대문자로 변환
        }
        // 2. Search 페이지로부터 입력된 값을 통해 DB에서 데이터 쿼리
        $query='select name,string,sequenceID from human_protein_reviewed where entrynumber="'.$human_protein.'"';// 프론트엔드 search 페이지에서 입력받은 타겟 단백질 엔트리넘버 값으로 DB 쿼리
        $protein1_all=$this->db->query($query)->result()[0];
        // 2-1. Search 페이지로부터 입력된 타겟 단백질에 대한 결과가 없을 경우 (Uniprot과 SRING 간 연동되지 않은 단백질 또는 상호작용하는 단백질이 없는 경우)
        if (empty($protein1_all)){// 타겟 단백질에 대한 DB 쿼리값이 없으면(사용자 타겟 단백질에 대한 정보가 DB에 없으면)
            header("Location:/search/noresult?s=".$human_protein);// noresult 페이지로 이동
            die();
        }
        if ($protein1_all->string == "NaN"){// 타겟 단백질에 대한 DB 쿼리값 중 string컬럼에 해당하는 값이 NaN이면(상호작용 단백질이 존재하지 않으면)
            header("Location:/search/nointeraction?s=".$human_protein);// nointeraction 페이지로 이동
            die();
        }
        // 2. Search 페이지로부터 입력된 값을 통해 DB에서 데이터 쿼리
        $query='select * from enzyme where id="'.$enzyme.'"';// 프론트엔드 search 페이지에서 입력받은 enzyme 정보를 DB 쿼리
        $enzyme_all=$this->db->query($query)->result()[0];
        $query='select name,binding_site,cleavability,mass,mass_c,mass_center,mass_n from crosslinker where id="'.$crosslinker.'"';// 프론트엔드 search 페이지에서 입력받은 crosslinker 정보를 DB 쿼리
        $crosslinker_all=$this->db->query($query)->result()[0];
        $query='select * from amino_acid_mass';// AA의 각 질량값 정보를 DB 쿼리
        $aa_mass_all=$this->db->query($query)->result();
        $query='select protein2,combined_score from protein_interaction where protein1="'.$protein1_all->string.'" order by combined_score desc limit '.$ranking;// 타겟 단백질의 string 정보로 상호작용하는 단백질들에 대한 DB 쿼리 시 프론트엔드 search 페이지에서 입력받은 ranking 개수만큼만 내림차순으로 쿼리
        $protein2_interaction_all=$this->db->query($query)->result();
        $protein2_string='';// 타겟 단백질과 상호작용하는 단백질들을 문자열 형대로 만들어주기 위한 빈 어레이 생성
        // 2-2. 타겟 단백질과 상호작용하는 단백질을 정보를 DB에서 쿼리해오기 위해 문자열로 치환 (아래 단계에서 STRING DB에서 string값 조회 후 해당 값으로 Uniprot에서 조회하여 정보를 가져오기 위함)
        for ($i=0;$i<count($protein2_interaction_all);$i++){// 상호작용하는 단백질들의 개수만큼 반복
            if ($i != 0){// 상호작용하는 단백질들이 하나라도 검색되면
                $protein2_string.=',';// 상호작용하는 단백질 하나 뒤에 콤마를 문자열로 이어 붙이기
            }
            $protein2_string.='"';// 상호작용하는 단백질이 있을 경우 맨 처음 쌍따옴표를 문자열로 이어 붙이기
            $protein2_string.=$protein2_interaction_all[$i]->protein2;// 쌍따옴표 뒤에 해당 단백질 정보를 문자열로 이어붙이기
            $protein2_string.='"';// 상호작용하는 단백질 정보 뒤에 쌍따옴표를 문자열로 이어붙이기 -> 최종적으로 "단백질명" 형태로 문자열 이어붙이기(DB 쿼리시 문자열로 입력을 받기 때문에 변환해주는 것)
        }
        // 2. Search 페이지로부터 입력된 값을 통해 DB에서 데이터 쿼리
        $query='select name,entrynumber,entryname,string,sequenceID from human_protein_reviewed where string IN ('.$protein2_string.') AND entrynumber not like "%-%"';// 문자열 형태로 변환한 상호작용 단백질들 정보로 각 상호작용하는 단백질들의 정보 DB 쿼리
        $protein2_all_query=$this->db->query($query)->result();
        // 2-3. 상호작용 단백질 중 STRING 및 Uniprot 결과와 연동되지 않은 단백질을 제거 및 Combined Score 값으로 내림차순 정렬 후 변수에 저장
        $protein2_all=[];// 상호작용 단백질들의 DB 쿼리 정보를 담을 빈 어레이 생성
        for ($i=0;$i<count($protein2_interaction_all);$i++){// 상호작용 단백질들의 개수만큼 반복
            for ($j=0;$j<count($protein2_all_query);$j++){// 상호작용 단백질들 중 string 컬럼 값을 가지는 단백질 개수만큼 반복
                if ($protein2_interaction_all[$i]->protein2==$protein2_all_query[$j]->string){// 상호작용 단백질들 중 string 컬럼 값을 가지는 단백질이면
                    $arr=[];// string 컬럼 값을 가지는 상호작용 단백질을 담을 빈 어레이 생성
                    $arr=[
                        'name'=>$protein2_all_query[$j]->name,
                        'entrynumber'=>$protein2_all_query[$j]->entrynumber,
                        'entryname'=>$protein2_all_query[$j]->entryname,
                        'string'=>$protein2_all_query[$j]->string,
                        'sequenceID'=>$protein2_all_query[$j]->sequenceID,
                        'combined_score'=>$protein2_interaction_all[$i]->combined_score
                    ];// string 컬럼 값을 가지는 상호단백질 정보를 arr 변수에 담고
                    array_push($protein2_all, $arr);// protein2_all 변수에 arr변수 값을 덮어쓰기 / 이하 상호작용 단백질로 지칭
                }
            }
        }
        unset($protein2_all_query);// 임시 사용한 변수 제거
        // 2-4. Result 페이지에서 Combined Score로 결과 요약 시 입력된 값으로 재쿼리 (Search 페이지에서 입력 시 Result 페이지에서 Combined Score로 입력된 값은 없으니 범위 설정이 없음)
        $interaction_info=[];// Result 페이지에서 사용자가 입력하는 Combined Score 범위에 해당하는 결과만 재도출, 해당 값을 쿼리 시 적용하여 재정렬하는 방식으로 결과 요약
        for ($i=0;$i<count($protein2_all);$i++){// 상호작용 단백질 개수만큼 반복
            $chk_cs='N';// Result 페이지에서 Combined Scroe에 대한 값을 입력하지 않을 경우 'N' 상태로 둠, 'N' 상태일 경우 조건에 맞는 값만 남기고 나머지는 제거하는 요약을 진행하지 않음
            if (!empty($score_min)){// Result 페이지에서 Combined Score 최소 값이 입력되면
                if ($protein2_all[$i]['combined_score']<$score_min){// 입력된 Combined Score 최소 값이 상호작용 단백질의 Combined Score 값보다 크면 (즉, 범위안에 들어오면)
                    $chk_cs='Y';// 'N' 값이 'Y'로 바뀜
                }
            }
            if (!empty($score_max)){// Result 페이지에서 Combined Score 최대 값이 입력되면
                if ($protein2_all[$i]['combined_score']>=$score_max){// 입력된 Combined Score 최대 값이 상호작용 단백질의 Combined Score 값보다 작거나 같으면 (즉, 범위안에 들어오면)
                    $chk_cs='Y';// 'N' 값이 'Y'로 바뀜
                }
            }
            if ($chk_cs=='N'){// 'N' 값 일 경우
                for ($j=0;$j<count($protein2_interaction_all);$j++){// 상호작용 단백질 개수만큼 반복하면서
                    if ($protein2_all[$i]['string']==$protein2_interaction_all[$j]->protein2){// 상호작용 단백질 중 string 컬럼 값을 가지는 단백질만 골라서
                        array_push($interaction_info, [
                            'name'=>$protein2_all[$i]['name'],
                            'score'=>$protein2_interaction_all[$j]->combined_score
                        ]);// interaction_info 변수에 덮어씀, 'Y'로 바뀌면 'N'에 해당하는 부분이 덮어써지지 않기 때문에 제거되는 효과를 가짐
                    }
                }
            }
        }
        $data['interaction_info']=$interaction_info;// 요약 결과를 data['interaction_inof] 에 덮어씀
        $sequenceID_arr=seq_digestion($protein1_all->sequenceID,$enzyme_all->cleavage_site,$enzyme_all->exception,$crosslinker_all->binding_site);// 타겟 단백질 시퀀스를 digestion (전체 시퀀스를 펩타이드 단편으로 만듬)
        $sequenceID_arr_unset=[];// Digestion 된 타겟 단백질 결과를 임시로 저장할 빈 어레이 생성
        for ($i=0;$i<count($sequenceID_arr);$i++){// 타겟 단백질의 시퀀스 개수만큼 반복 (타겟 단백질은 1개이기 때문에 무조건 1번 반복)
            if (strlen($sequenceID_arr[$i]['peptide'])>=$peptide_length_min&&strlen($sequenceID_arr[$i]['peptide'])<=$peptide_length_max){// 시퀀스 길이가 Search 페이지에서 입력받은 peptide length 길이 범위안에 들어오는 것만
                array_push($sequenceID_arr_unset,['peptide'=>$sequenceID_arr[$i]['peptide']]);// sequenceID_arr_unset 변수에 저장
            }
        }
        $sequenceID_arr=$sequenceID_arr_unset;// 임시 변수에서 digestion 후 특정 범위에 해당하는 시퀀스만을 저장
        unset($sequenceID_arr_unset);// 임시 변수 제거
        for ($i=0;$i<count($sequenceID_arr);$i++){// 특정 길이 범위를 가지는 시퀀스 개수만큼 반복
            $peptide_mass=(double)0;// 각 펩타이드의 질량값을 0으로 초기화
            for ($j=0;$j<strlen($sequenceID_arr[$i]['peptide']);$j++){// digestion 된 펩타이드 단편들의 길이만큼 반복
                for ($k=0;$k<count($aa_mass_all);$k++){// DB의 아미노산 개수만큼 반복
                    if ($sequenceID_arr[$i]['peptide'][$j]==$aa_mass_all[$k]->slc){// digestion된 펩타이드 문자열 각 각이 DB 아미노산의 slc 문자열과 일치하면
                        $peptide_mass=$peptide_mass+(double)$aa_mass_all[$k]->monoisotopic;// 해당 아미노산의 질량값을 더해줌 (질량값 0에서부터 누적 합)
                        if ($j==strlen($sequenceID_arr[$i]['peptide'])-1){// digestion된 펩타이드 단편들의 길이만큼 반복을 마치면
                            $peptide_mass=$peptide_mass;// peptide_mass 변수에 덮어씀
                        }
                    }
                }
                $sequenceID_arr[$i]['peptide_mass']=$peptide_mass;// 산출된 펩타이드 단편들의 질량값을 seqeunceID_arr 어레이에 peptide_mass 컬럼으로 저장
            }
            // 3. 타겟 단백질 펩타이드들을 단편화하여 이온으로 분리
            $peptide_frag=[];// 펩타이드 단편화 (이온값)을 위한 빈 어레이 생성
            for ($j=0;$j<strlen($sequenceID_arr[$i]['peptide']);$j++){// digestion 처리된 펩타이드들의 시퀀스 길이만큼 반복
                if ($j>0 && $j<strlen($sequenceID_arr[$i]['peptide'])){// 펩타이드 길이가 0보다 크고, 전체 시퀀스 길이보다 1 작으면 (y ion은 맨 마지막 인덱스 값이기 때문에 끝까지 돌면 안됨)
                    $ion=[];// 펩타이드 단편화 값을 저장할 빈어레이 생성
                    $b_ion="";// b ion에 해당하는 값을 저장할 빈어레이 생성
                    $y_ion="";// y ion에 해당하는 값을 저장할 빈어레이 생성
                    $b_ion=substr($sequenceID_arr[$i]['peptide'],0,$j);// digestion된 펩타이드 조각을 0번 인덱스부터 펩타이드 길이 -1에 해당하면 0번 인덱스 부터 1개씩 b ion변수에 하나씩 저장 (문자열의 일부분을 추출)
                    $y_ion=substr($sequenceID_arr[$i]['peptide'],$j);// digestion된 펩타이드 조각에서 1번 인덱스부터 끝까지 해당하는 값을 y ion 변수에 저장
                    array_push($ion,$b_ion);// ion 변수에 b ion 변수를 저장
                    array_push($ion,$y_ion);// ion 변수에 y ion 변수를 저장
                    $mass=[];// 단편화된 펩타이드 (ion) 의 질량값을 산출하기 위한 빈 어레이 생성
                    $mass_b=(double)0;// 단변화된 펩타이드 질량값 초기화
                    for ($k=0;$k<strlen($b_ion);$k++){// b ion의 길이만큼 반복
                        for ($l=0;$l<count($aa_mass_all);$l++){// DB 아미노산 개수만큼 반복
                            if ($b_ion[$k]==$aa_mass_all[$l]->slc){// b ion의 시퀀스 각 문자가 DB 아미노산 slc 값과 일치하면
                                $mass_b=$mass_b+(double)$aa_mass_all[$l]->monoisotopic;// 해당 아미노산에 대한 질량값을 합연산
                            }
                        }
                    }
                    array_push($mass,$mass_b);// mass 변수에 b ion mass 값을 추가
                    $mass_y=(double)0;// y ion 질량값 초기화
                    for ($k=0;$k<strlen($y_ion);$k++){// y ion의 시퀀스 길이만큼 반복
                        for ($l=0;$l<count($aa_mass_all);$l++){// DB 아미노산 개수만큼 반복
                            if ($y_ion[$k]==$aa_mass_all[$l]->slc){// y ion 시퀀스의 각 문자가 DB 아미노산 slc 값과 일치하면
                                $mass_y=$mass_y+(double)$aa_mass_all[$l]->monoisotopic;// 해당 아미노산에 대한 질량값을 합연산
                            }
                        }
                    }
                    array_push($mass,$mass_y);// mass 변수에 y ion mass 값을 추가
                    array_push($peptide_frag,['ion'=>$ion, 'ion_mass'=>$mass]);// prptide_frag 변수에 ion과 ion_mass 컬럼을 추가 후 저장
                }
            }
            $sequenceID_arr[$i]['peptide_frag']=$peptide_frag;// sequenceID_arr변수에 peptide_frag컬럼에 덮어쓰기
            unset($peptide_frag);// 임시 사용 변수 제거
        }
        $protein1_all->sequenceID_arr=$sequenceID_arr;// 타겟 단백질에 대한 정보에 sequenceID 값을 추가
        // 3. 상호작용 단백질들의 펩타이드들을 단편화하여 이온으로 분리
        for ($i=0;$i<count($protein2_all);$i++){// 상호작용 단백질 개수만큼 반복
            $sequenceID_arr=seq_digestion($protein2_all[$i]['sequenceID'],$enzyme_all->cleavage_site,$enzyme_all->exception,$crosslinker_all->binding_site);// 상호작용 단백질의 시퀀스를 펩타이드 조각으로 Digestion 처리
            $sequenceID_arr_unset=[];// 상호작용 단백질들의 펩타이드 조각을 임시로 담을 빈 어레이 생성
            for ($j=0;$j<count($sequenceID_arr);$j++){// 상호작용 단백질들의 펩타이드 조각 개수만큼 반복
                if (strlen($sequenceID_arr[$j]['peptide'])>=$peptide_length_min && strlen($sequenceID_arr[$j]['peptide'])<=$peptide_length_max){// 반약 펩타이드 조각의 길이가 Search 페이지에서 입력된 펩타이드 길이 범위에 해당하면
                    array_push($sequenceID_arr_unset, ['peptide'=>$sequenceID_arr[$j]['peptide']]);// 사용자가 입력한 펩타이드 길이 범위에 해당하는 펩타이드들만 변수에 저장
                }
            }
            $sequenceID_arr=$sequenceID_arr_unset;// 임시로 담은 변수 값을 원래 변수에 덮어씀
            unset($sequenceID_arr_unset);// 임시 사용 변수 제거
            for ($j=0;$j<count($sequenceID_arr);$j++){// Digestion 된 변수 개수만큼 반복
                $peptide_mass=(double)0;// 펩타이드 질량값 초기화
                for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++){// 각 펩타이드 조각의 시퀀스 길이만큼 반복
                    for ($l=0;$l<count($aa_mass_all);$l++){// DB에서 아미노산 개수만큼 반복
                        if ($sequenceID_arr[$j]['peptide'][$k]==$aa_mass_all[$l]->slc){// 각 펩타이드 조각의 시퀀스에 문자열이 DB의 아미노산의 slc 값과 일치하면
                            $peptide_mass=$peptide_mass+(double)$aa_mass_all[$l]->monoisotopic;// 특정 아미노산의 질량값만큼 합연산
                        }
                    }
                }
                $sequenceID_arr[$j]['peptide_mass']=$peptide_mass;// 산출된 펩타이드 조각의 질량값을 sequenceID_arr 변수의 peptide_mass 컬럼에 저장
                // 상호작용 단백질 펩타이드들을 단편화하여 이온으로 분리
                $peptide_frag=[];// 펩타이드 단편을 저장함 빈 어레이 생성
                for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++){// 상호작용 단백질의 펩타이드 시퀀스 길이만큼 반복
                    if ($k>0 && $k<strlen($sequenceID_arr[$j]['peptide'])){// 펩타이드 길이가 0보다 크고, 전체 시퀀스 길이보다 1 작으면 (y ion은 맨 마지막 인덱스 값이기 때문에 끝까지 돌면 안됨)
                        $ion=[];// 펩타이드 단편화 값을 저장할 빈어레이 생성
                        $b_ion='';// b ion에 해당하는 값을 저장할 빈어레이 생성
                        $y_ion='';// y ion에 해당하는 값을 저장할 빈어레이 생성
                        $b_ion=substr($sequenceID_arr[$j]['peptide'],0,$k);// digestion된 펩타이드 조각을 0번 인덱스부터 펩타이드 길이 -1에 해당하면 0번 인덱스 부터 1개씩 b ion변수에 하나씩 저장 (문자열의 일부분을 추출)
                        $y_ion=substr($sequenceID_arr[$j]['peptide'],$k);// digestion된 펩타이드 조각에서 1번 인덱스부터 끝까지 해당하는 값을 y ion 변수에 저장
                        array_push($ion,$b_ion);// ion 변수에 b ion 변수를 저장
                        array_push($ion,$y_ion);// ion 변수에 y ion 변수를 저장
                        $mass=[];// 단편화된 펩타이드 (ion) 의 질량값을 산출하기 위한 빈 어레이 생성
                        $mass_b=(double)0;// 단변화된 펩타이드 질량값 초기화
                        for ($l=0;$l<strlen($b_ion);$l++){// b ion의 길이만큼 반복
                            for ($m=0;$m<count($aa_mass_all);$m++){// DB 아미노산 개수만큼 반복
                                if ($b_ion[$l]==$aa_mass_all[$m]->slc){// b ion의 시퀀스 각 문자가 DB 아미노산 slc 값과 일치하면
                                    $mass_b=$mass_b+(double)$aa_mass_all[$m]->monoisotopic;// 해당 아미노산에 대한 질량값을 합연산
                                }
                            }
                        }
                        array_push($mass,$mass_b);// mass 변수에 b ion mass 값을 추가
                        $mass_y=(double)0;// y ion 질량값 초기화
                        for ($l=0;$l<strlen($y_ion);$l++){// y ion의 시퀀스 길이만큼 반복
                            for ($m=0;$m<count($aa_mass_all);$m++){// DB 아미노산 개수만큼 반복
                                if ($y_ion[$l]==$aa_mass_all[$m]->slc){// y ion 시퀀스의 각 문자가 DB 아미노산 slc 값과 일치하면
                                    $mass_y=$mass_y+(double)$aa_mass_all[$m]->monoisotopic;// 해당 아미노산에 대한 질량값을 합연산
                                }
                            }
                        }
                        array_push($mass,$mass_y);// mass 변수에 y ion mass 값을 추가
                        array_push($peptide_frag, ['ion'=>$ion, 'ion_mass'=>$mass]);// prptide_frag 변수에 ion과 ion_mass 컬럼을 추가 후 저장
                    }
                }
                $sequenceID_arr[$j]['peptide_frag']=$peptide_frag;// sequenceID_arr변수에 peptide_frag컬럼에 덮어쓰기
                unset($peptide_frag);// 임사 사용 변수 제거
            }
            $protein2_all[$i]['sequenceID_arr']=$sequenceID_arr;// 상호작용 단백질 정보를 담는 변수에 sequenceID_arr 컬럼을 만들고 덮어쓰기
        }
        // 4. 타겟 단백질에 대한 정보 통합 (이온 수준으로 분리한 모든 과정의 정보를 하나의 변수에 통합)
        $protein1_result=[];// 타겟 단백질에 대한 정보 병합할 빈 변수 생성
        for ($i=0;$i<count($protein1_all->sequenceID_arr);$i++){// 타겟 단백질의 sequenceID_arr 개수만큼 반복 (펩타이드 조각 개수)
            $protein1_peptide=$protein1_all->sequenceID_arr[$i]['peptide'];// 타겟 단백질 펩타이드를 protein1_all변수의 sequenceID_arr, peptide 컬럼에 저장
            $protein1_peptide_mass=$protein1_all->sequenceID_arr[$i]['peptide_mass'];// 타겟 단백질 펩타이드 질량값을 proteina1_all 변수의 sequenceID_arr, peptide_mass 컬럼에 저장
            for ($j=0;$j<count($protein1_all->sequenceID_arr[$i]['peptide_frag']);$j++){// 타겟 단백질의 단편화된 펩타이드 (이온) 개수만큼 반복
                for ($k=0;$k<count($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion']);$k++){// 타겟 단백질의 펩타이드 (이온)의 개수만큼 반복 (b ion, y ion 개수)
                    $protein1_ion=$protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k];// 타겟 단백질의 protein1_ion 변수에 각 이온 값을 저장 (b ion인지 y ion인지 변수로서 구문)
                    $protein1_ion_mass=$protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion_mass'][$k];// 타겟 단백질의 protein1_ion_mass 변수에 각 이온의 질량값을 저장
                    $protein1_ion_type='';// 저장한 이온 값이 b ion인지 y ion인지 별도로 표기 해주기 위한 변수 생성
                    $leng=strlen($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k]);// 타겟 단백질의 ion 컬럼의 값들의 문자열 길이 값을 저장 (b 3 와 같이 저장하기 위함)
                    if ($k==0){// 타겟 단백질의 ion 어레이가 0번째 어레이면 (k 값은 0과 1만 존재하며 위에서 ion 값을 저장할 때 b ion에 해당하는 것을 0에, y ion에 해당하는 것을 1에 저장)
                        $protein1_ion_type='b'.$leng;// ion type 변수를 생성하고, 해당 ion의 길이만큼 문자열 이어붙이기를 이용하여 저장
                    } else{// 타겟 단백질의 ion 어레이가 1번째 어레이면
                        $protein1_ion_type='y'.$leng;// ion type 변수를 생성하고, 해당 ion의 길이만큼 문자열 이어붙이기를 이용하여 저장
                        $protein1_ion_mass=$protein1_ion_mass+$h2o;// y ion이면 h2o 질량값을 더해줌 (질량 값 산출시 시퀀스 마지막 뒤에 h2o가 붙기 때문에 b ion에는 질량이 추가되지 않으며, y ion에만 추가됨)
                    }
                    array_push($protein1_result, [// 타겟 단백질에 대한 정보를 2차원으로 풀어줌
                        'protein1_peptide'=>$protein1_peptide,
                        'protein1_peptide_mass'=>$protein1_peptide_mass,
                        'protein1_ion'=>$protein1_ion,
                        'protein1_ion_type'=>$protein1_ion_type,
                        'protein1_ion_mass'=>$protein1_ion_mass
                    ]);
                }
            }
        }
        // 5. 상호작용 단백질들에 대한 정보 통합 (이온 수준으로 분리한 모든 과정의 정보를 하나의 변수에 통합)
        $protein2_result=[];// 타겟 단백질과 상호작용하는 단백질들에 대한 정보 병합
        for ($i=0;$i<count($protein2_all);$i++){// 상호작용 단백질의 개수만큼 반복 (Search 페이지에서 사용자가 확인하고자 하는 상호작용 단백질의 개수에 따라 여러개 일 수 있기 때문)
            for ($j=0;$j<count($protein2_all[$i]['sequenceID_arr']);$j++){// 상호작용 단백질의 sequenceID_arr 개수만큼 반복 (펩타이드 조각 개수)
                $protein2_peptide=$protein2_all[$i]['sequenceID_arr'][$j]['peptide'];// 상호작용 단백질 펩타이드를 protein2_all변수의 sequenceID_arr, peptide 컬럼에 저장
                $protein2_peptide_mass=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_mass'];// 상호작용 단백질 펩타이드 질량값을 proteina2_all 변수의 sequenceID_arr, peptide_mass 컬럼에 저장
                for ($k=0;$k<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag']);$k++){// 상호작용 단백질의 단편화된 펩타이드 (이온) 개수만큼 반복
                    for ($l=0;$l<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion']);$l++){// 상호작용 단백질의 펩타이드 (이온)의 개수만큼 반복 (b ion, y ion 개수)
                        $protein2_ion=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l];// 상호작용 단백질의 protein2_ion 변수에 각 이온 값을 저장 (b ion인지 y ion인지 변수로서 구문)
                        $protein2_ion_mass=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion_mass'][$l];// 상호작용 단백질의 protein2_ion_mass 변수에 각 이온의 질량값을 저장
                        $protein2_ion_type='';// 저장한 이온 값이 b ion인지 y ion인지 별도로 표기 해주기 위한 변수 생성
                        $leng=strlen($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l]);// 타겟 단백질의 ion 컬럼의 값들의 문자열 길이 값을 저장 (y 9 와 같이 저장하기 위함)
                        if ($l==0){// 상호작용 단백질의 ion 어레이가 0번째 어레이면 (k 값은 0과 1만 존재하며 위에서 ion 값을 저장할 때 b ion에 해당하는 것을 0에, y ion에 해당하는 것을 1에 저장)
                            $protein2_ion_type='b'.$leng;// ion type 변수를 생성하고, 해당 ion의 길이만큼 문자열 이어붙이기를 이용하여 저장
                        } else{// 상호작용 단백질의 ion 어레이가 1번째 어레이면
                            $protein2_ion_type='y'.$leng;// ion type 변수를 생성하고, 해당 ion의 길이만큼 문자열 이어붙이기를 이용하여 저장
                            $protein2_ion_mass=$protein2_ion_mass+$h2o;// y ion이면 h2o 질량값을 더해줌 (질량 값 산출시 시퀀스 마지막 뒤에 h2o가 붙기 때문에 b ion에는 질량이 추가되지 않으며, y ion에만 추가됨)
                        }
                        array_push($protein2_result,[// 상호작용 단백질에 대한 정보를 2차원으로 풀어줌
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
        // 6. 타겟 단백질 및 상호작용 단백질에 대한 정보를 하나로 병합 (2차원 배열로 변환)
        $result=[];// 타겟 단백질 및 상호작용 단백질에 대한 정보를 하나로 병합하기 위한 빈 변수 생성
        for ($i=0;$i<count($protein1_result);$i++){// 타겟 단백질에 대한 정보 개수만큼 반복
            $protein1_peptide=$protein1_result[$i]['protein1_peptide'];// 타겟 단백질의 펩타이드 시퀀스 정보를 2차원으로 치환
            $protein1_peptide_mass=$protein1_result[$i]['protein1_peptide_mass'];// 타겟 단백질의 펩타이드 시퀀스 질량값 정보를 2차원으로 치환
            $protein1_ion=$protein1_result[$i]['protein1_ion'];// 타겟 단백질의 단편화된 펩타이드 (이온) 정보를 2차원으로 치환
            $protein1_ion_type=$protein1_result[$i]['protein1_ion_type'];// 타겟 단백질의 단편화된 펩타이드 종류 (이온 종류, b or y) 정보를 2차원으로 치환
            $protein1_ion_mass=$protein1_result[$i]['protein1_ion_mass'];// 타겟 단백질의 단편화된 펩타이드 질량값 (이온 질량값) 정보를 2차원으로 치환
            for ($j=0;$j<count($protein2_result);$j++){// 상호작용 단백질들에 대한 정보 개수만큼 반복
                $protein2_peptide=$protein2_result[$j]['protein2_peptide'];// 상호작용 단백질들의 펩타이드 시퀀스 정보를 2차원으로 치환
                $protein2_peptide_mass=$protein2_result[$j]['protein2_peptide_mass'];// 상호작용 단백질들의 펩타이드 시퀀스 질량값 정보를 2차원으로 치환
                $protein2_ion=$protein2_result[$j]['protein2_ion'];// 상호작용 단백질들의 단편화된 펩타이드 (이온) 정보를 2차원으로 치환
                $protein2_ion_type=$protein2_result[$j]['protein2_ion_type'];// 상호작용 단백질들의 단편화된 펩타이드 종류 (이온 종류, b or y) 정보를 2차원으로 치환
                $protein2_ion=$protein2_result[$j]['protein2_ion_mass'];// 상호작용 단백질들의 단편화된 펩타이드 질량값 (이온 질량값) 정보를 2차원으로 치환
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
                ]);// 2차원으로 치환한 타겟 단백질, 상호작용 단백질에 대한 정보를 하나로 병합
            }
        }
        unset($protein1_result);// 임시 사용 변수 제거
        unset($protein2_result);// 임시 사용 변수 제거
        unset($protein1_peptide);// 임시 사용 변수 제거
        unset($protein1_peptide_mass);// 임시 사용 변수 제거
        unset($protein1_ion);// 임시 사용 변수 제거
        unset($protein1_ion_type);// 임시 사용 변수 제거
        unset($protein1_ion_mass);// 임시 사용 변수 제거
        unset($protein2_peptide);// 임시 사용 변수 제거
        unset($protein2_peptide_mass);// 임시 사용 변수 제거
        unset($protein2_ion);// 임시 사용 변수 제거
        unset($protein2_ion_type);// 임시 사용 변수 제거
        unset($protein2_ion_mass);// 임시 사용 변수 제거
        // 7. 펩타이드 수준에서 Cross linker가 바인딩하는 경우의 수 계산
        for ($i=0;$i<count($result);$i++){// 단백질 펩타이드 수준에서 크로스링커의 특성을 고려하여 결합하는 모든 경우의 수 별로 질량값 산출
            if ($crosslinker_all->cleavability=="Y"){// cleavability 특성을 갖는 크로스링커로 검색한다면
                $case=mass_case_all(// 바인딩하는 경우의 수를 계산하여 변수에 저장
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_peptide'],
                    $result[$i]['protein2_peptide'],
                    $result[$i]['protein1_peptide_mass'],
                    $result[$i]['protein2_peptide_mass'],
                    $crosslinker_all->mass_c,// cleavage 된 이후의 질량값을 각 각 더해줌
                    $crosslinker_all->mass_n// cleavage 된 이후의 질량값을 각 각 더해줌
                );
            } else{// cleavability 특성을 갖지 않는 크로스링커로 검색한다면
                $case=mass_case_all(// cleavability 특성을 갖지 않는 링크
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_peptide'],
                    $result[$i]['protein2_peptide'],
                    $result[$i]['protein1_peptide_mass'],
                    $result[$i]['protein2_peptide_mass'],
                    $crosslinker_all->mass,// cross linker 전체의 질량값을 각 각 더해줌 (cross linker 가 절단되지 않기 때문)
                    $crosslinker_all->mass// cross linker 전체의 질량값을 각 각 더해줌 (cross linker 가 절단되지 않기 때문)
                );
            }
            $result[$i]['case']=$case;// 위에서 병합한 result 변수에 case 컬럼을 생성하고 저장
        }
        $result_c=[];// 최종 결과를 임시로 저장한 빈 어레이 생성
        for ($i=0;$i<count($result);$i++){// 최종 result 변수의 개수만큼 반복
            for ($j=0;$j<count($result[$i]['case']);$j++){// cross linker 가 바인딩하는 경우의 수를 저장한 case 컬럼의 데이터 개수만큼 반복
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
                ]);// result_c 변수에 최종 결과를 2차 배열로 치환 및 저장
            }
        }
        $result=$result_c;// result_c의 결과를 result 배열에 덮어씀
        // 8. 펩타이드 단편화 (ion) 수준에서 cross linker 가 바인딩하는 경우의 수 계산
        for ($i=0;$i<count($result);$i++){// result 변수에 데이터 개수만큼 반복 (최종 결과의 데이터 개수)
            if ($crosslinker_all->cleavability=="Y"){// cross linker 가 cleavability 특성을 가지면
                $case=mass_case_all(// 바인딩하는 경우의 수를 계산하여 변수에 저장
                    $crosslinker_all->binding_site,// 
                    $result[$i]['protein1_ion'],
                    $result[$i]['protein2_ion'],
                    $result[$i]['protein1_ion_mass'],
                    $result[$i]['protein2_ion_mass'],
                    $crosslinker_all->mass_c,// cleavage 된 이후의 질량값을 각 각 더해줌
                    $crosslinker_all->mass_n// cleavage 된 이후의 질량값을 각 각 더해줌
                );
            } else{// cleavability 특성을 갖지 않는 크로스링커로 검색한다면
                $case=mass_case_all(// cleavability 특성을 갖지 않는 링크
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_ion'],
                    $result[$i]['protein2_ion'],
                    $result[$i]['protein1_ion_mass'],
                    $result[$i]['protein2_ion_mass'],
                    $crosslinker_all->mass,// cross linker 전체의 질량값을 각 각 더해줌 (cross linker 가 절단되지 않기 때문)
                    $crosslinker_all->mass// cross linker 전체의 질량값을 각 각 더해줌 (cross linker 가 절단되지 않기 때문)
                );
            }
            $result[$i]['case']=$case;
        }
        // 8-1. 단편화된 펩타이드 (이온) 수준에 대한 모든 결과 병합
        $result_c=[];// 결과를 임시로 저장할 빈 어레이 생성
        for ($i=0;$i<count($result);$i++){// 최종 결과의 데이터 개수만큼 반복
            for ($j=0;$j<count($result[$i]['case']);$j++){// 최공 결과에서 이온 수준에 대한 데이터 개수만큼 반복
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
                ]);// 이온 수준에 대한 결과를 result_c (임시 변수)에 저장
            }
        }
        $result=$result_c;// 최종 결과 변수에 임시 변수에 저장한 내용을 덮어씀
        // 9. Modification 에 따른 경우의 수 산출
        if ($carbamidomethyl != 'N' || $oxidation != "N"){// modification에 따른 경우의 수 산출. 기본적으로 변이 정보가 선택되지 않으면 'N'값으로 둔다 / 새로운 변이 정보가 추가되면 '||' 로 조건 추가 후 아래 if문 추가
            $result_c=[];// 최종 결과 배열을 담을 빈 어레이 생성
            $chk_m_str='/[^';// 변이가 일어나는 AA 자리 문자열 체크(C,M) / 해당 문자열을 제외하고 나머지 문자열을 다 제거하는 정규표현식
            if ($carbamidomethyl != 'N'){// carbamidomethyl 변이를 체크하면
                $chk_m_str=$chk_m_str.'C';// 해당 변이가 일어나는 아미노산 자리 값을 추가 저장
            }
            if ($oxidation != 'N'){// oxidation 변이를 체크하면  /// 새로운 변이 정보를 추가하면 해당 if문을 아래 추가해줘야 함
                $chk_m_str=$chk_m_str.'M';// 해당 변이가 일어나는 아미노산 자리 값을 추가 저장
            }// 새로운 변이가 추가되면 if문을 해당 변이에 맞는 if문을 추가
            $chk_m_str=$chk_m_str.']/';// 문자열 이어 붙이기로 정규표현식 완성
            for ($i=0;$i<count($result);$i++){// 최종 결과 변수의 데이터 개수만큼 반복
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
                ];// item 변수에 최종 결과 변수 값 임시 저장
                // 변이에 해당하는 아미노산 문자열 모두가 포함되어 있는지 확인
                $protein1_peptide_str=preg_replace($chk_m_str,"",$result[$i]['protein1_peptide']);// 타겟단백질 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (C,M) 제외한 나머지 값 제거
                $protein2_peptide_str=preg_replace($chk_m_str,"",$result[$i]['protein2_peptide']);// 상호작용 단백질의 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (C,M) 제외한 나머지 값 제거
                $protein1_ion_str=preg_replace($chk_m_str,"",$result[$i]['protein1_ion']);// 타겟단백질 이온 문자열에서 변이에 해당하는 AA 문자열 (C,M) 제외한 나머지 값 제거
                $protein2_ion_str=preg_replace($chk_m_str,"",$result[$i]['protein2_ion']);// 상호작용 단백질의 이온 문자열에서 변이에 해당하는 AA 문자열 (C,M) 제외한 나머지 값 제거
                // carbamidomethyl 변이에 해당하는 아미노산 문자열이 포함되어 있는지 확인
                $protein1_peptide_c=preg_replace('/[^C]/',"",$protein1_peptide_str);// 타겟단백질 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (C) 제외한 나머지 값 제거  
                $protein2_peptide_c=preg_replace('/[^C]/',"",$protein2_peptide_str);// 상호작용 단백질의 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (C) 제외한 나머지
                $protein1_ion_c=preg_replace('/[^C]/',"",$protein1_peptide_str);// 타겟단백질 이온 문자열에서 변이에 해당하는 AA 문자열 (C) 제외한 나머지 값 제거
                $protein2_ion_c=preg_replace('/[^C]/',"",$protein2_peptide_str);// 상호작용 단백질의 이온 문자열에서 변이에 해당하는 AA 문자열 (C) 제외한 나머지 값 제거
                // oxidation 변이에 해당하는 아미노산 문자열이 포함되어 있는지 확인
                $protein1_peptide_m=preg_replace('/[^M]/',"",$protein1_peptide_str);// 타겟단백질 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (M) 제외한 나머지 값 제거  
                $protein2_peptide_m=preg_replace('/[^M]/',"",$protein2_peptide_str);// 상호작용 단백질의 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (M) 제외한 나머지
                $protein1_ion_m=preg_replace('/[^M]/',"",$protein1_ion_str);// 타겟단백질 이온 문자열에서 변이에 해당하는 AA 문자열 (M) 제외한 나머지 값 제거
                $protein2_ion_m=preg_replace('/[^M]/',"",$protein2_ion_str);// 상호작용 단백질의 이온 문자열에서 변이에 해당하는 AA 문자열 (M) 제외한 나머지 값 제거
                // 각 변이에 대한 모든 경우의 수 계산
                $protein1_peptide_case=modification_all_case($protein1_peptide_str);// 타겟 단백질 펩타이드 시퀀스에서 변이가 일어나는 모든 경우의 수 계산
                $protein2_peptide_case=modification_all_case($protein2_peptide_str);// 상호작용 단백질 펩타이드 시퀀스에서 변이가 일어나는 모든 경우의 수 계산
                $protein1_ion_case=modification_all_case($protein1_ion_str);// 타겟 단백질 펩타이드 단편 (이온) 시퀀스에서 변이가 일어나는 모든 경우의 수 계산
                $protein2_ion_case=modification_all_case($protein2_ion_str);// 상호작용 단백질 펩타이드 단편 (이온) 시퀀스에서 변이가 일어나는 모든 경우의 수 계산
                // 변이에 대한 모든 경우의 수 산출
                $protein1_peptide_case_del_arr=[];// 타겟 단백질 펩타이드에서 변이 경우의 수 처리할 값을 임시 저장할 변수 생성
                $protein2_peptide_case_del_arr=[];// 상호작용 단백질 펩타이드에서 변이 경우의 수 처리할 값을 임시 저장할 변수 생성
                $protein1_ion_case_del_arr=[];// 타겟 단백질 펩타이드 단편 (이온) 에서 변이 경우의 수 처리할 값을 임시 저장할 변수 생성
                $protein2_ion_case_del_arr=[];// 상호작용 단백질 펩타이드 단편 (이온) 에서 변이 경우의 수 처리할 값을 임시 저장할 변수 생성
                for ($j=0;$j<count($protein1_peptide_case);$j++){// 타겟 단백질 펩타이드에 대한 modification 경우의 수 만큼 반복
                    $del='N';// 변이에 해당하는 아미노산 문자열을 포함할 경우 'N' 으로 설정
                    if ($carbamidomethyl=='S'){// carbamidomethyl 이 static으로 선택되고
                        if ($protein1_peptide_case[$j] != ''){// 타겟 단백질 펩타이드에서 변이 경우의 수 값이 존재하면
                            if (strpos($protein1_peptide_case[$j],$protein1_peptide_c) !== FALSE){// 아미노산 C의 위치를 발견하면 'N' 값 유지(C 아미노산이 존재하면), 포함 유무만 체크
                            } else {// 변이에 해당하는 아미노산이 존재하지 않을 경우
                                $del='Y';// 'Y' 값으로 변경
                            }
                        }
                    }
                    if ($oxidation == 'S'){// oxidation 이 static으로 선택되고
                        if ($protein1_peptide_case[$j] != ''){// 타겟 단백질 펩타이드에서 변이의 경우의 수 값이 존재하면
                            if (strpos($protein1_peptide_case[$j],$protein1_peptide_m) !== FALSE){// 아미노산 M의 위치를 발견하면 'N' 값 유지(M 아미노산이 존재하면), 포함 유무만 체크
                            } else {// 변이에 해당하는 아미노산이 존재하지 않을 경우
                                $del='Y';// 'Y' 값으로 변경
                            }
                        }
                    }
                    array_push($protein1_peptide_case_del_arr,$del);// del 변수의 값을 저장
                }
                for ($j=0;$j<count($protein1_peptide_case_del_arr);$j++){// del 변수 값을 저장한 변수의 데이터 개수만큼 반복
                    if ($protein1_peptide_case_del_arr[$j] == 'Y'){// del 변수 값이 'Y' 이면 변이에 해당하는 아미노산이 존재하지 않기 때문에
                        unset($protein1_peptide_case[$j]);// 타겟 단백질 펩타이드의 변이에 대한 모든 경우의 수 삭제 (고려하지 않음)
                    }
                }
                $protein1_peptide_case=array_values($protein1_peptide_case);// 타겟 단백질 펩타이드에 대한 변이 경우의 수 값을 반환하고 저장 (CMM 시퀀스에서 C, C, CM 과 같은 경우의 수 값)
                // 상호작용 단백질 펩타이드에 대한 변이 경우의 수 산출
                for ($j=0;$j<count($protein2_peptide_case);$j++){// 상호작용 단백질 펩타이드에 대한 변이 경우의 수 만큼 반복, 아래 코드는 위 타겟 단백질 펩타이드의 변이 코드와 동일
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
                // 타겟 단백질 펩타이드 단편 (ion) 에 대한 변이 경우의 수 산출
                for ($j=0;$j<count($protein1_ion_case);$j++){// 타겟 단백질 펩아티드 단편 (ion) 에 대한 변이 경우의 수 만큼 반복, 아래 코드는 위 타겟 단백질 펩타이드의 변이 코드와 동일
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
                // 상호작용 단백질 펩타이드 단편 (ion) 에 대한 변이 경우의 수 반복
                for ($j=0;$j<count($protein2_ion_case);$j++){// 상호작용 단백질 펩아티드 단편 (ion) 에 대한 변이 경우의 수 만큼 반복, 아래 코드는 위 타겟 단백질 펩타이드의 변이 코드와 동일
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
                // 변이 경우의 수에 따른 질량값 추가
                for ($j=0;$j<count($protein1_peptide_case);$j++){// 타겟 단백질 펩타이드의 변이 경우의 수 만큼 반복
                    $item['protein1_peptide_c_term_mass']=$result[$i]['protein1_peptide_c_term_mass'];// item 변수에 타겟 단백질에 대한 질량값을 매핑
                    $item['protein1_peptide_n_term_mass']=$result[$i]['protein1_peptide_n_term_mass'];// item 변수에 타겟 단백질에 대한 질량값을 매핑
                    $sum_p1p_c=(double)0;// 타겟 단백질 펩타이드의 변이 (cabarmidomethyl) 경우의 질량값 초기화
                    $sum_p1p_m=(double)0;// 타겟 단백질 펩타이드의 변이 (oxidation) 경우의 질량값 초기화
                    $sum_p1p_c=mb_substr_count($protein1_peptide_case[$j],'C')*(double)$modification[0]->mass;// 타겟 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                    $sum_p1p_m=mb_substr_count($protein1_peptide_case[$j],'M')*(double)$modification[1]->mass;// 타겟 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                    $item['protein1_peptide_c_term_mass']=$item['protein1_peptide_c_term_mass']+$sum_p1p_c+$sum_p1p_m;// 타겟 단백질 펩타이드 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                    $item['protein1_peptide_n_term_mass']=$item['protein1_peptide_n_term_mass']+$sum_p1p_c+$sum_p1p_m;// 타겟 단백질 펩타이드 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                    for ($k=0;$k<count($protein2_peptide_case);$k++){// 상호작용 단백질 펩타이드의 변이 경우의 수에 따른 질량값 계산
                        $item['protein2_peptide_c_term_mass']=$result[$i]['protein2_peptide_c_term_mass'];// 상호작용 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                        $item['protein2_peptide_n_term_mass']=$result[$i]['protein2_peptide_n_term_mass'];// 상호작용 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                        $sum_p2p_c=(double)0;// 상호작용 단백질 펩타이드의 변이 (cabarmidomethyl) 경우의 질량값 초기화
                        $sum_p2p_m=(double)0;// 상호작용 단백질 펩타이드의 변이 (oxidation) 경우의 질량값 초기화
                        $sum_p2p_c=mb_substr_count($protein2_peptide_case[$k],'C')*(double)$modification[0]->mass;// 상호작용 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                        $sum_p2p_m=mb_substr_count($protein2_peptide_case[$k],'M')*(double)$modification[1]->mass;// 상호작용 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                        $item['protein2_peptide_c_term_mass']=$item['protein2_peptide_c_term_mass']+$sum_p2p_c+$sum_p2p_m;// 상호작용 단백질 펩타이드 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                        $item['protein2_peptide_n_term_mass']=$item['protein2_peptide_n_term_mass']+$sum_p2p_c+$sum_p2p_m;// 상호작용 단백질 펩타이드 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                        for ($l=0;$l<count($protein1_ion_case);$l++){// 타겟 단백질 ion의 변이 경우의 수에 따른 질량값 계산
                            $item['protein1_ion_c_term_mass']=$result[$i]['protein1_ion_c_term_mass'];// 타겟 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                            $item['protein1_ion_n_term_mass']=$result[$i]['protein1_ion_n_term_mass'];// 타겟 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                            $sum_p1i_c=(double)0;// 타겟 단백질 펩타이드 단편 (이온) 의 변이 (cabarmidomethyl) 경우의 질량값 초기화
                            $sum_p1i_m=(double)0;// 타겟 단백질 펩타이드 단편 (이온) 의 변이 (oxidation) 경우의 질량값 초기화
                            $sum_p1i_c=mb_substr_count($protein1_ion_case[$l],'C')*(double)$modification[0]->mass;// 타겟 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                            $sum_p1i_m=mb_substr_count($protein1_ion_case[$l],'M')*(double)$modification[1]->mass;// 타겟 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                            $item['protein1_ion_c_term_mass']=$item['protein1_ion_c_term_mass']+$sum_p1i_c+$sum_p1i_m;// 타겟 단백질 펩타이드 단편 (이온) 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                            $item['protein1_ion_n_term_mass']=$item['protein1_ion_n_term_mass']+$sum_p1i_c+$sum_p1i_m;// 타겟 단백질 펩타이드 단편 (이온) 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                            for ($m=0;$m<count($protein2_ion_case);$m++){// 상호작용 단백질 ion의 변이 경우의 수에 따른 질량값 계산
                                $item['protein2_ion_c_term_mass']=$result[$i]['protein2_ion_c_term_mass'];// 상호작용 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                                $item['protein2_ion_n_term_mass']=$result[$i]['protein2_ion_n_term_mass'];// 상호작용 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                                $sum_p2i_c=(double)0;// 상호작용 단백질 펩타이드 단편 (이온) 의 변이 (cabarmidomethyl) 경우의 질량값 초기화
                                $sum_p2i_m=(double)0;// 상호작용 단백질 펩타이드 단편 (이온) 의 변이 (oxidation) 경우의 질량값 초기화
                                $sum_p2i_c=mb_substr_count($protein2_ion_case[$m],'C')*(double)$modification[0]->mass;// 상호작용 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                                $sum_p2i_m=mb_substr_count($protein2_ion_case[$m],'M')*(double)$modification[1]->mass;// 상호작용 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                                $item['protein2_ion_c_term_mass']=$item['protein2_ion_c_term_mass']+$sum_p2i_c+$sum_p2i_m;// 상호작용 단백질 펩타이드 단편 (이온) 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                                $item['protein2_ion_n_term_mass']=$item['protein2_ion_n_term_mass']+$sum_p2i_c+$sum_p2i_m;// 상호작용 단백질 펩타이드 단편 (이온) 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                                array_push($result_c, $item);// 변이에 의해 추가 산출된 질량값 및 모든 결과를 result_c 변수에 저장
                            }
                        }
                    }
                }
            }
            $result=$result_c;// result_c 변수를 result 변수 (최종 결과 변수)에 덮어씀
        }
        // 10. 최종 산출된 결과에서 전하에 따른 질량값 연산
        if (count($peptidecharge) >= 1 || count($ioncharge) >= 1){// Search 페이지에서 펩타이드 (Precursor) 전하와 이온 (Product) 전하 값이 여러개 중복 선택되면
            $result_c=(array)[];// 임시로 저장할 빈 어레이 생성
            for ($i=0;$i<count($result);$i++){// 최종 결과의 데이터 개수만큼 반복
                for ($j=0;$j<count($peptidecharge);$j++){// Search 페이지에서 선택된 펩타이드 (Precursor) 전하 개수 만큼 반복
                    for ($k=0;$k<count($ioncharge);$k++){// Search 페이지에서 선택된 이온 (Product) 전하 개수 만큼 반복
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
                        ]);// result_c 변수에 전하로 나눈 질량값 및 최종 정보 저장, 펩타이드 질량에 해당하는 값은 펩타이드 (Precursor) 전하로 나누고, 펩타이드 단편화 (이온) 질량에 해당하는 값은 이온 (Product) 전하로 나눠줌
                    }
                }
            }
            $result=$result_c;// result_c 변수를 result 변수에 덮어씀 (최종 결과가 담기는 곳은 result 변수)
        }
        // 11. Result 페이지에서 Prego 결과를 통한 결과 요약
        if ($p1_hp_peptide != '' || $p2_hp_peptide != ''){// Prego를 통한 결과 요약에 대한 입력 값이 타겟 단백질, 상호작용 단백질 모두 입력되면 
            $result_c=[];// 임시로 데이터 저장을 위한 빈 어레이 생성
            $p1_hp_peptide_arr=explode(' ',$p1_hp_peptide);// 타겟 단백질에 대한 Prego 결과 값을 복사하여 Result 페이지에 입력하면 공백을 기준으로 split하여 어레이로 변환
            $p2_hp_peptide_arr=explode(' ',$p2_hp_peptide);// 상호작용 단백질에 대한 Prego 결과 값을 복사하여 Result 페이지에 입력하면 공백을 기준으로 split하여 어레이로 변환
            for ($i=0;$i<count($result);$i++){// 최종 결과에 담긴 데이터 수 만큼 반복 (전체를 돌면서 Preogo 결과에 해당하는 펩타이드 시퀀스를 확인하기 위함)
                $chk_summary='Y';// 결과 요약을 위한 값이 입력되지 않을때 값을 'Y' 로 설정 (Prego 결과 or Combined Score 값 or 둘 다)
                for ($j=0;$j<count($p1_hp_peptide_arr);$j++){// 타겟 단백질에 대한 Prego 결과 펩타이드 개수만큼 반복
                    if (strpos($result[$i]['protein1_peptide'],$p1_hp_peptide_arr[$j]) !== FALSE){// 최종 결과에서 타겟 단백질 펩타이드에 대한 시퀀스와 Prego 결과 시퀀스를 포함하면 스킵
                    } else {// 최종 결과에서 타겟 단백질 펩타이드에 대한 시퀀스와 Prego 결과 시퀀스를 포함하지 않으면
                        $chk_summary='N';// 'Y' 값을 'N' 값으로 변환
                    }
                }
                for ($j=0;$j<count($p2_hp_peptide_arr);$j++){// 상호작용 단백질에 대한 Prego 결과 펩타이드 개수만큼 반복
                    if (strpos($result[$i]['protein2_peptide'],$p2_hp_peptide_arr[$j]) !== FALSE){// 최종 결과에서 상호작용 단백질 펩타이드에 대한 시퀀스와 Prego 결과 시퀀스를 포함하면 스킵
                    } else {// 최종 결과에서 타겟 단백질 펩타이드에 대한 시퀀스와 Prego 결과 시퀀스를  포함하지 않으면
                        $chk_summary='N';// 'Y' 값을 'N' 값으로 변환
                    }
                }
                if ($chk_summary == 'Y'){// 반약 'Y' 값일 경우
                    array_push($result_c,$result[$i]);//  Result 페이지에서 Prego의 결과 값이 포함되면 해당 값들만 새로운 변수에 덮어씀 (전체 결과에서 특정 결과만 남기는 효과)
                }
            }
            $result=$result_c;// Prego에 해당하는 값들만 최종결과 출력 변수인 result 변수에 덮어씀
        }
        // 12. Result 페이지에서 Combined Score 중 상한값 또는 하한값만 (1개의 값)만 입력하는 경우 예외처리
        if (!empty($score_min) || !empty($score_max)){// Result 페이지에서 상호작용할 확률 값을 통해 결과 요약을 위한 값이 입력되지 않으면
            $result_c=[];// 조건문 내부에서 결과를 임시로 담을 빈 어레이 생성
            for ($i=0;$i<count($result);$i++){// 최종 결과의 데이터 개수만큼 반복
                $chk_summary='Y';// 결과 요약을 위한 값이 입력되지 않을때 값을 'Y' 로 설정 (Prego 결과 or Combined Score 값 or 둘 다)
                if (!empty($score_min)){// Result 페이지에서 Combined Score 의 하한값이 입력되고
                    if ($result[$i]['combined_score']<$score_min){// 최종 결과 값에서 입력도니 Combined Score 값보다 작은 값들에 대해
                        $chk_summary='N';// 'Y' 값을 'N' 으로 변경
                    }
                }                
                if (!empty($score_max)){// Result 페이지에서 Combined Score 의 상한값 입력되고
                    if ($result[$i]['combined_score']>=$score_max){// 최종 결과 값에서 입력도니 Combined Score 값보다 큰 값들에 대해
                        $chk_summary='N';// 'Y' 값을 'N' 으로 변경
                    }
                }
                if ($chk_summary == 'Y'){// 'Y' 값일 경우
                    array_push($result_c,$result[$i]);// Result 페이지에서 Combined Score 값에 해당하는 값들만 result_c 변수에 저장
                }
            }
            $result=$result_c;// result_c 변수 값들 최종 결과를 담는 변수인 resut 변수에 덮어씀
        }
        $result=array_unique($result,SORT_REGULAR);// 최종 결과 중 모든 값이 같은 중복 데이터 제거
        $result=array_values($result);// 최종 결과 재정렬
        // 13. 페이지 네이션
        $page_list=20;// 한 페이지에 보여줄 행 개수
        $page_group=10;// 한 그룹으로 보여줄 페이지 개수 (프론트엔드에 보여질 총 페이지 개수)
        $page_now=$this->input->post('page_now');// 현재 사용자가 보고 있는 페이지 값을 프론트엔드에서 받아옴
        $page_total=count($result);// 최종 결과 값의 개수 확인
        $page_group_total=ceil($page_total/$page_list);// 최종 결과 개수에서 한 페이지에 보여줄 행 개수만큼 나눈 후 올림처리 (10개의 데이터를 페이지당 3개씩 보여줄 경우 총 4개의 페이지가 필요하기 때문)
        if (empty($page_now)){// 만약 프론트엔드로 부터 현자 사용자가 보고 있는 페이지 값이 비어 있으면 (처음 결과가 출력된 경우 자동으로 1페이지를 보여주기 위함)
            $page_now=1;// 1페이지를 기본으로 보도록 설정
        }
        if ($page_now == 1){// 만약 사용자가 1페이지를 보고 있다면
            $page_list_start=0;// 
        } else{
            $page_list_start=($page_now-1)*$page_list;// 0번째 행을 설정, 기본 1페이지를 0페이지로 만드는 과정
        }
        $result=array_slice($result,$page_list_start,$page_list);// 최종결과 배열을 0부터 20개의 행씩 나누고
        $pagination_count=[];// 페이지네이션 배열을 담기위한 빈 어레이 생성
        $page_group_start=$page_now-$page_group;// 페이지네이션에서 시작되는 페이지 숫자 값
        $page_group_end=$page_now+$page_group;// 페이지네이션에서 끝나는 페이지 숫자 값
        for ($i=$page_group_start;$i<$page_group_end;$i++){// 페이지네이션 시작 값에부터 끝나는 값가지 반복
            if ($i > 1){// 페이지네이션은 음수일 수 없으니 (최소 1페이지 이기 때문)
                if ($i <$page_group_total){// 페이지네이션에서 페이지 총 그룹을 초과하지 않으면
                    array_push($pagination_count,$i);// 페이지네이션 배열에 추가
                }
            }
        }
        $result_id=1;// Result 페이지에서 결과 값의 행 시작 값 (인덱스값)
        if ($page_now != 1){// 사용자가 보고 있는 페이지가 1페이지가 아니면
            $result_id=1+($page_now*$page_list);// 페이지그룹 +1 번째 인덱스값부터 시작 (페이지당 10개 행을 보여주고 2페이지를 보고 있을 경우 인덱스 값은 11)
        }
        for ($i=0;$i<count($result);$i++){// 최종 결과의 데이터 개수만큼 반복
            $result[$i]['id']=$result_id;// 최종 결과 변수에 'id'컬럼을 만들고 인덱스 값을 추가 저장
            $result_id=$result_id+1;// 인덱스 값은 1씩 계속 증가
        }
        if (!empty($score_min)){// Result 페이지에서 결과 요약을 위해 Combined Score 하한값을 값을 입력하면
            $score_min=$score_min/1000;// Combined Score 값을 실수 (예: 0.999) 로 변환
        }
        if (!empty($score_max)){// Result 페이지에서 결과 요약을 위해 Combined Score 상한값을 값을 입력하면
            $score_max=$score_max/1000;// Combined Score 값을 실수 (예: 0.999) 로 변환
        }
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
        ];// 프론트엔드로 부터 입력받은 값들을 저장하여 설정 유지
        // 프론트엔드로 부터 입력받은 값들을 저장 (다시 프론트엔드에서 그대로 출력해줄 경우를 위해 저장하여 가지고 있음)
        $data['input_info']=(array)$input_info;
        $data['search_protein']=$protein1_all->name;
        $data['crosslinker']=$crosslinker_all;
        $data['ioncharge']=$ioncharge;
        $data['result']=$result;
        // Result 페이지 구성
        $this->load->view('common/header');
        $this->load->view('search/result',$data);
        $this->load->view('common/footer');
        $end_memory = memory_get_usage();
        // print_r(($end_memory-$start_memory)/1000000000);
    }
    public function noresult(){// noresult 페이지 구성
        $this->load->view('common/header');
        $this->load->view('search/noresult');
        $this->load->view('common/footer');
    }
    public function nointeraction(){// nointeraction 페이지 구성
        $this->load->view('common/header');
        $this->load->view('search/nointeraction');
        $this->load->view('common/footer');
    }
    public function result_csv(){// CSV format으로 결과를 Export하기 위한 함수
        // 위 result function 중 1~12 에 해당하는 코드를 복사하여 사용
        // 1. Search 페이지에서 검색을 위한 입력 정보 저장
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
        $proton=1.00727649;// protin 질량값
        $h2o=18.01056468403;// h2o 질량값
        // 1-1. Result 페이지에서 결과 요약을 위한 입력 정보 저장 (Combined Score 값을 통한 결과 요약)
        $score_min=$this->input->post('score_min');// 결과페이지에서 결과 요약을 위한 상호작용하는 확률 score값 범위
        $score_max=$this->input->post('score_max');// 결과페이지에서 결과 요약을 위한 상호작용하는 확률 score값 범위
        // 1-2. 1-1에서 입력된 결과 요약을 위한 정보가 입력되면 해당 값 실수 (수수점 확률값)에서 정수(0~999 사이의 값)로 변환
        if (!empty($score_min)){// 상호작용하는 확률 score값이 입력되면
            $score_min=$score_min*1000;// 확률 score값에 1000을 곱함 (확률값은 소수점자리이며, DB에서는 1~1000점으로 분류하기 때문)
        }
        if (!empty($score_max)){// 상호작용하는 확률 score값이 입력되면
            $score_max=$score_max*1000;// 확률 score값에 1000을 곱함 (확률값은 소수점자리이며, DB에서는 1~1000점으로 분류하기 때문)
        }
        // 1-3. Result 페이지에서 결과요약을 위한 입력 정보 저장 (Prego 결과를 통한 결과 요약)
        $p1_hp_peptide=$this->input->post('p1_hp_peptide');// prego을 통한 타겟 단백질의 peptide sequence 값을 입력
        if ($p1_hp_peptide != ""){// 타겟 단백질의 peptide sequence 값이 입력되면
            $p1_hp_peptide=strtoupper($p1_hp_peptide);// sequence 갑들을 대문자로 변환
        }
        $p2_hp_peptide=$this->input->post('p2_hp_peptide');// prego를 통한 타겟 단백질과 상호작용하는 단백질들의 peptide sequence 값을 입력
        if ($p2_hp_peptide != ""){// 타겟 단백질과 상호작용하는 단백질들의 peptide sequence 값이 입력되면
            $p2_hp_peptide=strtoupper($p2_hp_peptide);// sequence 값들을 대문자로 변환
        }
        // 2. Search 페이지로부터 입력된 값을 통해 DB에서 데이터 쿼리
        $query='select name,string,sequenceID from human_protein_reviewed where entrynumber="'.$human_protein.'"';// 프론트엔드 search 페이지에서 입력받은 타겟 단백질 엔트리넘버 값으로 DB 쿼리
        $protein1_all=$this->db->query($query)->result()[0];
        // 2-1. Search 페이지로부터 입력된 타겟 단백질에 대한 결과가 없을 경우 (Uniprot과 SRING 간 연동되지 않은 단백질 또는 상호작용하는 단백질이 없는 경우)
        if (empty($protein1_all)){// 타겟 단백질에 대한 DB 쿼리값이 없으면(사용자 타겟 단백질에 대한 정보가 DB에 없으면)
            header("Location:/search/noresult?s=".$human_protein);// noresult 페이지로 이동
            die();
        }
        if ($protein1_all->string == "NaN"){// 타겟 단백질에 대한 DB 쿼리값 중 string컬럼에 해당하는 값이 NaN이면(상호작용 단백질이 존재하지 않으면)
            header("Location:/search/nointeraction?s=".$human_protein);// nointeraction 페이지로 이동
            die();
        }
        // 2. Search 페이지로부터 입력된 값을 통해 DB에서 데이터 쿼리
        $query='select * from enzyme where id="'.$enzyme.'"';// 프론트엔드 search 페이지에서 입력받은 enzyme 정보를 DB 쿼리
        $enzyme_all=$this->db->query($query)->result()[0];
        $query='select name,binding_site,cleavability,mass,mass_c,mass_center,mass_n from crosslinker where id="'.$crosslinker.'"';// 프론트엔드 search 페이지에서 입력받은 crosslinker 정보를 DB 쿼리
        $crosslinker_all=$this->db->query($query)->result()[0];
        $query='select * from amino_acid_mass';// AA의 각 질량값 정보를 DB 쿼리
        $aa_mass_all=$this->db->query($query)->result();
        $query='select protein2,combined_score from protein_interaction where protein1="'.$protein1_all->string.'" order by combined_score desc limit '.$ranking;// 타겟 단백질의 string 정보로 상호작용하는 단백질들에 대한 DB 쿼리 시 프론트엔드 search 페이지에서 입력받은 ranking 개수만큼만 내림차순으로 쿼리
        $protein2_interaction_all=$this->db->query($query)->result();
        $protein2_string='';// 타겟 단백질과 상호작용하는 단백질들을 문자열 형대로 만들어주기 위한 빈 어레이 생성
        // 2-2. 타겟 단백질과 상호작용하는 단백질을 정보를 DB에서 쿼리해오기 위해 문자열로 치환 (아래 단계에서 STRING DB에서 string값 조회 후 해당 값으로 Uniprot에서 조회하여 정보를 가져오기 위함)
        for ($i=0;$i<count($protein2_interaction_all);$i++){// 상호작용하는 단백질들의 개수만큼 반복
            if ($i != 0){// 상호작용하는 단백질들이 하나라도 검색되면
                $protein2_string.=',';// 상호작용하는 단백질 하나 뒤에 콤마를 문자열로 이어 붙이기
            }
            $protein2_string.='"';// 상호작용하는 단백질이 있을 경우 맨 처음 쌍따옴표를 문자열로 이어 붙이기
            $protein2_string.=$protein2_interaction_all[$i]->protein2;// 쌍따옴표 뒤에 해당 단백질 정보를 문자열로 이어붙이기
            $protein2_string.='"';// 상호작용하는 단백질 정보 뒤에 쌍따옴표를 문자열로 이어붙이기 -> 최종적으로 "단백질명" 형태로 문자열 이어붙이기(DB 쿼리시 문자열로 입력을 받기 때문에 변환해주는 것)
        }
        // 2. Search 페이지로부터 입력된 값을 통해 DB에서 데이터 쿼리
        $query='select name,entrynumber,entryname,string,sequenceID from human_protein_reviewed where string IN ('.$protein2_string.') AND entrynumber not like "%-%"';// 문자열 형태로 변환한 상호작용 단백질들 정보로 각 상호작용하는 단백질들의 정보 DB 쿼리
        $protein2_all_query=$this->db->query($query)->result();
        // 2-3. 상호작용 단백질 중 STRING 및 Uniprot 결과와 연동되지 않은 단백질을 제거 및 Combined Score 값으로 내림차순 정렬 후 변수에 저장
        $protein2_all=[];// 상호작용 단백질들의 DB 쿼리 정보를 담을 빈 어레이 생성
        for ($i=0;$i<count($protein2_interaction_all);$i++){// 상호작용 단백질들의 개수만큼 반복
            for ($j=0;$j<count($protein2_all_query);$j++){// 상호작용 단백질들 중 string 컬럼 값을 가지는 단백질 개수만큼 반복
                if ($protein2_interaction_all[$i]->protein2==$protein2_all_query[$j]->string){// 상호작용 단백질들 중 string 컬럼 값을 가지는 단백질이면
                    $arr=[];// string 컬럼 값을 가지는 상호작용 단백질을 담을 빈 어레이 생성
                    $arr=[
                        'name'=>$protein2_all_query[$j]->name,
                        'entrynumber'=>$protein2_all_query[$j]->entrynumber,
                        'entryname'=>$protein2_all_query[$j]->entryname,
                        'string'=>$protein2_all_query[$j]->string,
                        'sequenceID'=>$protein2_all_query[$j]->sequenceID,
                        'combined_score'=>$protein2_interaction_all[$i]->combined_score
                    ];// string 컬럼 값을 가지는 상호단백질 정보를 arr 변수에 담고
                    array_push($protein2_all, $arr);// protein2_all 변수에 arr변수 값을 덮어쓰기 / 이하 상호작용 단백질로 지칭
                }
            }
        }
        unset($protein2_all_query);// 임시 사용한 변수 제거
        // 2-4. Result 페이지에서 Combined Score로 결과 요약 시 입력된 값으로 재쿼리 (Search 페이지에서 입력 시 Result 페이지에서 Combined Score로 입력된 값은 없으니 범위 설정이 없음)
        $interaction_info=[];// Result 페이지에서 사용자가 입력하는 Combined Score 범위에 해당하는 결과만 재도출, 해당 값을 쿼리 시 적용하여 재정렬하는 방식으로 결과 요약
        for ($i=0;$i<count($protein2_all);$i++){// 상호작용 단백질 개수만큼 반복
            $chk_cs='N';// Result 페이지에서 Combined Scroe에 대한 값을 입력하지 않을 경우 'N' 상태로 둠, 'N' 상태일 경우 조건에 맞는 값만 남기고 나머지는 제거하는 요약을 진행하지 않음
            if (!empty($score_min)){// Result 페이지에서 Combined Score 최소 값이 입력되면
                if ($protein2_all[$i]['combined_score']<$score_min){// 입력된 Combined Score 최소 값이 상호작용 단백질의 Combined Score 값보다 크면 (즉, 범위안에 들어오면)
                    $chk_cs='Y';// 'N' 값이 'Y'로 바뀜
                }
            }
            if (!empty($score_max)){// Result 페이지에서 Combined Score 최대 값이 입력되면
                if ($protein2_all[$i]['combined_score']>=$score_max){// 입력된 Combined Score 최대 값이 상호작용 단백질의 Combined Score 값보다 작거나 같으면 (즉, 범위안에 들어오면)
                    $chk_cs='Y';// 'N' 값이 'Y'로 바뀜
                }
            }
            if ($chk_cs=='N'){// 'N' 값 일 경우
                for ($j=0;$j<count($protein2_interaction_all);$j++){// 상호작용 단백질 개수만큼 반복하면서
                    if ($protein2_all[$i]['string']==$protein2_interaction_all[$j]->protein2){// 상호작용 단백질 중 string 컬럼 값을 가지는 단백질만 골라서
                        array_push($interaction_info, [
                            'name'=>$protein2_all[$i]['name'],
                            'score'=>$protein2_interaction_all[$j]->combined_score
                        ]);// interaction_info 변수에 덮어씀, 'Y'로 바뀌면 'N'에 해당하는 부분이 덮어써지지 않기 때문에 제거되는 효과를 가짐
                    }
                }
            }
        }
        $data['interaction_info']=$interaction_info;// 요약 결과를 data['interaction_inof] 에 덮어씀
        $sequenceID_arr=seq_digestion($protein1_all->sequenceID,$enzyme_all->cleavage_site,$enzyme_all->exception,$crosslinker_all->binding_site);// 타겟 단백질 시퀀스를 digestion (전체 시퀀스를 펩타이드 단편으로 만듬)
        $sequenceID_arr_unset=[];// Digestion 된 타겟 단백질 결과를 임시로 저장할 빈 어레이 생성
        for ($i=0;$i<count($sequenceID_arr);$i++){// 타겟 단백질의 시퀀스 개수만큼 반복 (타겟 단백질은 1개이기 때문에 무조건 1번 반복)
            if (strlen($sequenceID_arr[$i]['peptide'])>=$peptide_length_min&&strlen($sequenceID_arr[$i]['peptide'])<=$peptide_length_max){// 시퀀스 길이가 Search 페이지에서 입력받은 peptide length 길이 범위안에 들어오는 것만
                array_push($sequenceID_arr_unset,['peptide'=>$sequenceID_arr[$i]['peptide']]);// sequenceID_arr_unset 변수에 저장
            }
        }
        $sequenceID_arr=$sequenceID_arr_unset;// 임시 변수에서 digestion 후 특정 범위에 해당하는 시퀀스만을 저장
        unset($sequenceID_arr_unset);// 임시 변수 제거
        for ($i=0;$i<count($sequenceID_arr);$i++){// 특정 길이 범위를 가지는 시퀀스 개수만큼 반복
            $peptide_mass=(double)0;// 각 펩타이드의 질량값을 0으로 초기화
            for ($j=0;$j<strlen($sequenceID_arr[$i]['peptide']);$j++){// digestion 된 펩타이드 단편들의 길이만큼 반복
                for ($k=0;$k<count($aa_mass_all);$k++){// DB의 아미노산 개수만큼 반복
                    if ($sequenceID_arr[$i]['peptide'][$j]==$aa_mass_all[$k]->slc){// digestion된 펩타이드 문자열 각 각이 DB 아미노산의 slc 문자열과 일치하면
                        $peptide_mass=$peptide_mass+(double)$aa_mass_all[$k]->monoisotopic;// 해당 아미노산의 질량값을 더해줌 (질량값 0에서부터 누적 합)
                        if ($j==strlen($sequenceID_arr[$i]['peptide'])-1){// digestion된 펩타이드 단편들의 길이만큼 반복을 마치면
                            $peptide_mass=$peptide_mass;// peptide_mass 변수에 덮어씀
                        }
                    }
                }
                $sequenceID_arr[$i]['peptide_mass']=$peptide_mass;// 산출된 펩타이드 단편들의 질량값을 seqeunceID_arr 어레이에 peptide_mass 컬럼으로 저장
            }
            // 3. 타겟 단백질 펩타이드들을 단편화하여 이온으로 분리
            $peptide_frag=[];// 펩타이드 단편화 (이온값)을 위한 빈 어레이 생성
            for ($j=0;$j<strlen($sequenceID_arr[$i]['peptide']);$j++){// digestion 처리된 펩타이드들의 시퀀스 길이만큼 반복
                if ($j>0 && $j<strlen($sequenceID_arr[$i]['peptide'])){// 펩타이드 길이가 0보다 크고, 전체 시퀀스 길이보다 1 작으면 (y ion은 맨 마지막 인덱스 값이기 때문에 끝까지 돌면 안됨)
                    $ion=[];// 펩타이드 단편화 값을 저장할 빈어레이 생성
                    $b_ion="";// b ion에 해당하는 값을 저장할 빈어레이 생성
                    $y_ion="";// y ion에 해당하는 값을 저장할 빈어레이 생성
                    $b_ion=substr($sequenceID_arr[$i]['peptide'],0,$j);// digestion된 펩타이드 조각을 0번 인덱스부터 펩타이드 길이 -1에 해당하면 0번 인덱스 부터 1개씩 b ion변수에 하나씩 저장 (문자열의 일부분을 추출)
                    $y_ion=substr($sequenceID_arr[$i]['peptide'],$j);// digestion된 펩타이드 조각에서 1번 인덱스부터 끝까지 해당하는 값을 y ion 변수에 저장
                    array_push($ion,$b_ion);// ion 변수에 b ion 변수를 저장
                    array_push($ion,$y_ion);// ion 변수에 y ion 변수를 저장
                    $mass=[];// 단편화된 펩타이드 (ion) 의 질량값을 산출하기 위한 빈 어레이 생성
                    $mass_b=(double)0;// 단변화된 펩타이드 질량값 초기화
                    for ($k=0;$k<strlen($b_ion);$k++){// b ion의 길이만큼 반복
                        for ($l=0;$l<count($aa_mass_all);$l++){// DB 아미노산 개수만큼 반복
                            if ($b_ion[$k]==$aa_mass_all[$l]->slc){// b ion의 시퀀스 각 문자가 DB 아미노산 slc 값과 일치하면
                                $mass_b=$mass_b+(double)$aa_mass_all[$l]->monoisotopic;// 해당 아미노산에 대한 질량값을 합연산
                            }
                        }
                    }
                    array_push($mass,$mass_b);// mass 변수에 b ion mass 값을 추가
                    $mass_y=(double)0;// y ion 질량값 초기화
                    for ($k=0;$k<strlen($y_ion);$k++){// y ion의 시퀀스 길이만큼 반복
                        for ($l=0;$l<count($aa_mass_all);$l++){// DB 아미노산 개수만큼 반복
                            if ($y_ion[$k]==$aa_mass_all[$l]->slc){// y ion 시퀀스의 각 문자가 DB 아미노산 slc 값과 일치하면
                                $mass_y=$mass_y+(double)$aa_mass_all[$l]->monoisotopic;// 해당 아미노산에 대한 질량값을 합연산
                            }
                        }
                    }
                    array_push($mass,$mass_y);// mass 변수에 y ion mass 값을 추가
                    array_push($peptide_frag,['ion'=>$ion, 'ion_mass'=>$mass]);// prptide_frag 변수에 ion과 ion_mass 컬럼을 추가 후 저장
                }
            }
            $sequenceID_arr[$i]['peptide_frag']=$peptide_frag;// sequenceID_arr변수에 peptide_frag컬럼에 덮어쓰기
            unset($peptide_frag);// 임시 사용 변수 제거
        }
        $protein1_all->sequenceID_arr=$sequenceID_arr;// 타겟 단백질에 대한 정보에 sequenceID 값을 추가
        // 3. 상호작용 단백질들의 펩타이드들을 단편화하여 이온으로 분리
        for ($i=0;$i<count($protein2_all);$i++){// 상호작용 단백질 개수만큼 반복
            $sequenceID_arr=seq_digestion($protein2_all[$i]['sequenceID'],$enzyme_all->cleavage_site,$enzyme_all->exception,$crosslinker_all->binding_site);// 상호작용 단백질의 시퀀스를 펩타이드 조각으로 Digestion 처리
            $sequenceID_arr_unset=[];// 상호작용 단백질들의 펩타이드 조각을 임시로 담을 빈 어레이 생성
            for ($j=0;$j<count($sequenceID_arr);$j++){// 상호작용 단백질들의 펩타이드 조각 개수만큼 반복
                if (strlen($sequenceID_arr[$j]['peptide'])>=$peptide_length_min && strlen($sequenceID_arr[$j]['peptide'])<=$peptide_length_max){// 반약 펩타이드 조각의 길이가 Search 페이지에서 입력된 펩타이드 길이 범위에 해당하면
                    array_push($sequenceID_arr_unset, ['peptide'=>$sequenceID_arr[$j]['peptide']]);// 사용자가 입력한 펩타이드 길이 범위에 해당하는 펩타이드들만 변수에 저장
                }
            }
            $sequenceID_arr=$sequenceID_arr_unset;// 임시로 담은 변수 값을 원래 변수에 덮어씀
            unset($sequenceID_arr_unset);// 임시 사용 변수 제거
            for ($j=0;$j<count($sequenceID_arr);$j++){// Digestion 된 변수 개수만큼 반복
                $peptide_mass=(double)0;// 펩타이드 질량값 초기화
                for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++){// 각 펩타이드 조각의 시퀀스 길이만큼 반복
                    for ($l=0;$l<count($aa_mass_all);$l++){// DB에서 아미노산 개수만큼 반복
                        if ($sequenceID_arr[$j]['peptide'][$k]==$aa_mass_all[$l]->slc){// 각 펩타이드 조각의 시퀀스에 문자열이 DB의 아미노산의 slc 값과 일치하면
                            $peptide_mass=$peptide_mass+(double)$aa_mass_all[$l]->monoisotopic;// 특정 아미노산의 질량값만큼 합연산
                        }
                    }
                }
                $sequenceID_arr[$j]['peptide_mass']=$peptide_mass;// 산출된 펩타이드 조각의 질량값을 sequenceID_arr 변수의 peptide_mass 컬럼에 저장
                // 상호작용 단백질 펩타이드들을 단편화하여 이온으로 분리
                $peptide_frag=[];// 펩타이드 단편을 저장함 빈 어레이 생성
                for ($k=0;$k<strlen($sequenceID_arr[$j]['peptide']);$k++){// 상호작용 단백질의 펩타이드 시퀀스 길이만큼 반복
                    if ($k>0 && $k<strlen($sequenceID_arr[$j]['peptide'])){// 펩타이드 길이가 0보다 크고, 전체 시퀀스 길이보다 1 작으면 (y ion은 맨 마지막 인덱스 값이기 때문에 끝까지 돌면 안됨)
                        $ion=[];// 펩타이드 단편화 값을 저장할 빈어레이 생성
                        $b_ion='';// b ion에 해당하는 값을 저장할 빈어레이 생성
                        $y_ion='';// y ion에 해당하는 값을 저장할 빈어레이 생성
                        $b_ion=substr($sequenceID_arr[$j]['peptide'],0,$k);// digestion된 펩타이드 조각을 0번 인덱스부터 펩타이드 길이 -1에 해당하면 0번 인덱스 부터 1개씩 b ion변수에 하나씩 저장 (문자열의 일부분을 추출)
                        $y_ion=substr($sequenceID_arr[$j]['peptide'],$k);// digestion된 펩타이드 조각에서 1번 인덱스부터 끝까지 해당하는 값을 y ion 변수에 저장
                        array_push($ion,$b_ion);// ion 변수에 b ion 변수를 저장
                        array_push($ion,$y_ion);// ion 변수에 y ion 변수를 저장
                        $mass=[];// 단편화된 펩타이드 (ion) 의 질량값을 산출하기 위한 빈 어레이 생성
                        $mass_b=(double)0;// 단변화된 펩타이드 질량값 초기화
                        for ($l=0;$l<strlen($b_ion);$l++){// b ion의 길이만큼 반복
                            for ($m=0;$m<count($aa_mass_all);$m++){// DB 아미노산 개수만큼 반복
                                if ($b_ion[$l]==$aa_mass_all[$m]->slc){// b ion의 시퀀스 각 문자가 DB 아미노산 slc 값과 일치하면
                                    $mass_b=$mass_b+(double)$aa_mass_all[$m]->monoisotopic;// 해당 아미노산에 대한 질량값을 합연산
                                }
                            }
                        }
                        array_push($mass,$mass_b);// mass 변수에 b ion mass 값을 추가
                        $mass_y=(double)0;// y ion 질량값 초기화
                        for ($l=0;$l<strlen($y_ion);$l++){// y ion의 시퀀스 길이만큼 반복
                            for ($m=0;$m<count($aa_mass_all);$m++){// DB 아미노산 개수만큼 반복
                                if ($y_ion[$l]==$aa_mass_all[$m]->slc){// y ion 시퀀스의 각 문자가 DB 아미노산 slc 값과 일치하면
                                    $mass_y=$mass_y+(double)$aa_mass_all[$m]->monoisotopic;// 해당 아미노산에 대한 질량값을 합연산
                                }
                            }
                        }
                        array_push($mass,$mass_y);// mass 변수에 y ion mass 값을 추가
                        array_push($peptide_frag, ['ion'=>$ion, 'ion_mass'=>$mass]);// prptide_frag 변수에 ion과 ion_mass 컬럼을 추가 후 저장
                    }
                }
                $sequenceID_arr[$j]['peptide_frag']=$peptide_frag;// sequenceID_arr변수에 peptide_frag컬럼에 덮어쓰기
                unset($peptide_frag);// 임사 사용 변수 제거
            }
            $protein2_all[$i]['sequenceID_arr']=$sequenceID_arr;// 상호작용 단백질 정보를 담는 변수에 sequenceID_arr 컬럼을 만들고 덮어쓰기
        }
        // 4. 타겟 단백질에 대한 정보 통합 (이온 수준으로 분리한 모든 과정의 정보를 하나의 변수에 통합)
        $protein1_result=[];// 타겟 단백질에 대한 정보 병합할 빈 변수 생성
        for ($i=0;$i<count($protein1_all->sequenceID_arr);$i++){// 타겟 단백질의 sequenceID_arr 개수만큼 반복 (펩타이드 조각 개수)
            $protein1_peptide=$protein1_all->sequenceID_arr[$i]['peptide'];// 타겟 단백질 펩타이드를 protein1_all변수의 sequenceID_arr, peptide 컬럼에 저장
            $protein1_peptide_mass=$protein1_all->sequenceID_arr[$i]['peptide_mass'];// 타겟 단백질 펩타이드 질량값을 proteina1_all 변수의 sequenceID_arr, peptide_mass 컬럼에 저장
            for ($j=0;$j<count($protein1_all->sequenceID_arr[$i]['peptide_frag']);$j++){// 타겟 단백질의 단편화된 펩타이드 (이온) 개수만큼 반복
                for ($k=0;$k<count($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion']);$k++){// 타겟 단백질의 펩타이드 (이온)의 개수만큼 반복 (b ion, y ion 개수)
                    $protein1_ion=$protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k];// 타겟 단백질의 protein1_ion 변수에 각 이온 값을 저장 (b ion인지 y ion인지 변수로서 구문)
                    $protein1_ion_mass=$protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion_mass'][$k];// 타겟 단백질의 protein1_ion_mass 변수에 각 이온의 질량값을 저장
                    $protein1_ion_type='';// 저장한 이온 값이 b ion인지 y ion인지 별도로 표기 해주기 위한 변수 생성
                    $leng=strlen($protein1_all->sequenceID_arr[$i]['peptide_frag'][$j]['ion'][$k]);// 타겟 단백질의 ion 컬럼의 값들의 문자열 길이 값을 저장 (b 3 와 같이 저장하기 위함)
                    if ($k==0){// 타겟 단백질의 ion 어레이가 0번째 어레이면 (k 값은 0과 1만 존재하며 위에서 ion 값을 저장할 때 b ion에 해당하는 것을 0에, y ion에 해당하는 것을 1에 저장)
                        $protein1_ion_type='b'.$leng;// ion type 변수를 생성하고, 해당 ion의 길이만큼 문자열 이어붙이기를 이용하여 저장
                    } else{// 타겟 단백질의 ion 어레이가 1번째 어레이면
                        $protein1_ion_type='y'.$leng;// ion type 변수를 생성하고, 해당 ion의 길이만큼 문자열 이어붙이기를 이용하여 저장
                        $protein1_ion_mass=$protein1_ion_mass+$h2o;// y ion이면 h2o 질량값을 더해줌 (질량 값 산출시 시퀀스 마지막 뒤에 h2o가 붙기 때문에 b ion에는 질량이 추가되지 않으며, y ion에만 추가됨)
                    }
                    array_push($protein1_result, [// 타겟 단백질에 대한 정보를 2차원으로 풀어줌
                        'protein1_peptide'=>$protein1_peptide,
                        'protein1_peptide_mass'=>$protein1_peptide_mass,
                        'protein1_ion'=>$protein1_ion,
                        'protein1_ion_type'=>$protein1_ion_type,
                        'protein1_ion_mass'=>$protein1_ion_mass
                    ]);
                }
            }
        }
        // 5. 상호작용 단백질들에 대한 정보 통합 (이온 수준으로 분리한 모든 과정의 정보를 하나의 변수에 통합)
        $protein2_result=[];// 타겟 단백질과 상호작용하는 단백질들에 대한 정보 병합
        for ($i=0;$i<count($protein2_all);$i++){// 상호작용 단백질의 개수만큼 반복 (Search 페이지에서 사용자가 확인하고자 하는 상호작용 단백질의 개수에 따라 여러개 일 수 있기 때문)
            for ($j=0;$j<count($protein2_all[$i]['sequenceID_arr']);$j++){// 상호작용 단백질의 sequenceID_arr 개수만큼 반복 (펩타이드 조각 개수)
                $protein2_peptide=$protein2_all[$i]['sequenceID_arr'][$j]['peptide'];// 상호작용 단백질 펩타이드를 protein2_all변수의 sequenceID_arr, peptide 컬럼에 저장
                $protein2_peptide_mass=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_mass'];// 상호작용 단백질 펩타이드 질량값을 proteina2_all 변수의 sequenceID_arr, peptide_mass 컬럼에 저장
                for ($k=0;$k<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag']);$k++){// 상호작용 단백질의 단편화된 펩타이드 (이온) 개수만큼 반복
                    for ($l=0;$l<count($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion']);$l++){// 상호작용 단백질의 펩타이드 (이온)의 개수만큼 반복 (b ion, y ion 개수)
                        $protein2_ion=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l];// 상호작용 단백질의 protein2_ion 변수에 각 이온 값을 저장 (b ion인지 y ion인지 변수로서 구문)
                        $protein2_ion_mass=$protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion_mass'][$l];// 상호작용 단백질의 protein2_ion_mass 변수에 각 이온의 질량값을 저장
                        $protein2_ion_type='';// 저장한 이온 값이 b ion인지 y ion인지 별도로 표기 해주기 위한 변수 생성
                        $leng=strlen($protein2_all[$i]['sequenceID_arr'][$j]['peptide_frag'][$k]['ion'][$l]);// 타겟 단백질의 ion 컬럼의 값들의 문자열 길이 값을 저장 (y 9 와 같이 저장하기 위함)
                        if ($l==0){// 상호작용 단백질의 ion 어레이가 0번째 어레이면 (k 값은 0과 1만 존재하며 위에서 ion 값을 저장할 때 b ion에 해당하는 것을 0에, y ion에 해당하는 것을 1에 저장)
                            $protein2_ion_type='b'.$leng;// ion type 변수를 생성하고, 해당 ion의 길이만큼 문자열 이어붙이기를 이용하여 저장
                        } else{// 상호작용 단백질의 ion 어레이가 1번째 어레이면
                            $protein2_ion_type='y'.$leng;// ion type 변수를 생성하고, 해당 ion의 길이만큼 문자열 이어붙이기를 이용하여 저장
                            $protein2_ion_mass=$protein2_ion_mass+$h2o;// y ion이면 h2o 질량값을 더해줌 (질량 값 산출시 시퀀스 마지막 뒤에 h2o가 붙기 때문에 b ion에는 질량이 추가되지 않으며, y ion에만 추가됨)
                        }
                        array_push($protein2_result,[// 상호작용 단백질에 대한 정보를 2차원으로 풀어줌
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
        // 6. 타겟 단백질 및 상호작용 단백질에 대한 정보를 하나로 병합 (2차원 배열로 변환)
        $result=[];// 타겟 단백질 및 상호작용 단백질에 대한 정보를 하나로 병합하기 위한 빈 변수 생성
        for ($i=0;$i<count($protein1_result);$i++){// 타겟 단백질에 대한 정보 개수만큼 반복
            $protein1_peptide=$protein1_result[$i]['protein1_peptide'];// 타겟 단백질의 펩타이드 시퀀스 정보를 2차원으로 치환
            $protein1_peptide_mass=$protein1_result[$i]['protein1_peptide_mass'];// 타겟 단백질의 펩타이드 시퀀스 질량값 정보를 2차원으로 치환
            $protein1_ion=$protein1_result[$i]['protein1_ion'];// 타겟 단백질의 단편화된 펩타이드 (이온) 정보를 2차원으로 치환
            $protein1_ion_type=$protein1_result[$i]['protein1_ion_type'];// 타겟 단백질의 단편화된 펩타이드 종류 (이온 종류, b or y) 정보를 2차원으로 치환
            $protein1_ion_mass=$protein1_result[$i]['protein1_ion_mass'];// 타겟 단백질의 단편화된 펩타이드 질량값 (이온 질량값) 정보를 2차원으로 치환
            for ($j=0;$j<count($protein2_result);$j++){// 상호작용 단백질들에 대한 정보 개수만큼 반복
                $protein2_peptide=$protein2_result[$j]['protein2_peptide'];// 상호작용 단백질들의 펩타이드 시퀀스 정보를 2차원으로 치환
                $protein2_peptide_mass=$protein2_result[$j]['protein2_peptide_mass'];// 상호작용 단백질들의 펩타이드 시퀀스 질량값 정보를 2차원으로 치환
                $protein2_ion=$protein2_result[$j]['protein2_ion'];// 상호작용 단백질들의 단편화된 펩타이드 (이온) 정보를 2차원으로 치환
                $protein2_ion_type=$protein2_result[$j]['protein2_ion_type'];// 상호작용 단백질들의 단편화된 펩타이드 종류 (이온 종류, b or y) 정보를 2차원으로 치환
                $protein2_ion=$protein2_result[$j]['protein2_ion_mass'];// 상호작용 단백질들의 단편화된 펩타이드 질량값 (이온 질량값) 정보를 2차원으로 치환
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
                ]);// 2차원으로 치환한 타겟 단백질, 상호작용 단백질에 대한 정보를 하나로 병합
            }
        }
        unset($protein1_result);// 임시 사용 변수 제거
        unset($protein2_result);// 임시 사용 변수 제거
        unset($protein1_peptide);// 임시 사용 변수 제거
        unset($protein1_peptide_mass);// 임시 사용 변수 제거
        unset($protein1_ion);// 임시 사용 변수 제거
        unset($protein1_ion_type);// 임시 사용 변수 제거
        unset($protein1_ion_mass);// 임시 사용 변수 제거
        unset($protein2_peptide);// 임시 사용 변수 제거
        unset($protein2_peptide_mass);// 임시 사용 변수 제거
        unset($protein2_ion);// 임시 사용 변수 제거
        unset($protein2_ion_type);// 임시 사용 변수 제거
        unset($protein2_ion_mass);// 임시 사용 변수 제거
        // 7. 펩타이드 수준에서 Cross linker가 바인딩하는 경우의 수 계산
        for ($i=0;$i<count($result);$i++){// 단백질 펩타이드 수준에서 크로스링커의 특성을 고려하여 결합하는 모든 경우의 수 별로 질량값 산출
            if ($crosslinker_all->cleavability=="Y"){// cleavability 특성을 갖는 크로스링커로 검색한다면
                $case=mass_case_all(// 바인딩하는 경우의 수를 계산하여 변수에 저장
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_peptide'],
                    $result[$i]['protein2_peptide'],
                    $result[$i]['protein1_peptide_mass'],
                    $result[$i]['protein2_peptide_mass'],
                    $crosslinker_all->mass_c,// cleavage 된 이후의 질량값을 각 각 더해줌
                    $crosslinker_all->mass_n// cleavage 된 이후의 질량값을 각 각 더해줌
                );
            } else{// cleavability 특성을 갖지 않는 크로스링커로 검색한다면
                $case=mass_case_all(// cleavability 특성을 갖지 않는 링크
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_peptide'],
                    $result[$i]['protein2_peptide'],
                    $result[$i]['protein1_peptide_mass'],
                    $result[$i]['protein2_peptide_mass'],
                    $crosslinker_all->mass,// cross linker 전체의 질량값을 각 각 더해줌 (cross linker 가 절단되지 않기 때문)
                    $crosslinker_all->mass// cross linker 전체의 질량값을 각 각 더해줌 (cross linker 가 절단되지 않기 때문)
                );
            }
            $result[$i]['case']=$case;// 위에서 병합한 result 변수에 case 컬럼을 생성하고 저장
        }
        $result_c=[];// 최종 결과를 임시로 저장한 빈 어레이 생성
        for ($i=0;$i<count($result);$i++){// 최종 result 변수의 개수만큼 반복
            for ($j=0;$j<count($result[$i]['case']);$j++){// cross linker 가 바인딩하는 경우의 수를 저장한 case 컬럼의 데이터 개수만큼 반복
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
                ]);// result_c 변수에 최종 결과를 2차 배열로 치환 및 저장
            }
        }
        $result=$result_c;// result_c의 결과를 result 배열에 덮어씀
        // 8. 펩타이드 단편화 (ion) 수준에서 cross linker 가 바인딩하는 경우의 수 계산
        for ($i=0;$i<count($result);$i++){// result 변수에 데이터 개수만큼 반복 (최종 결과의 데이터 개수)
            if ($crosslinker_all->cleavability=="Y"){// cross linker 가 cleavability 특성을 가지면
                $case=mass_case_all(// 바인딩하는 경우의 수를 계산하여 변수에 저장
                    $crosslinker_all->binding_site,// 
                    $result[$i]['protein1_ion'],
                    $result[$i]['protein2_ion'],
                    $result[$i]['protein1_ion_mass'],
                    $result[$i]['protein2_ion_mass'],
                    $crosslinker_all->mass_c,// cleavage 된 이후의 질량값을 각 각 더해줌
                    $crosslinker_all->mass_n// cleavage 된 이후의 질량값을 각 각 더해줌
                );
            } else{// cleavability 특성을 갖지 않는 크로스링커로 검색한다면
                $case=mass_case_all(// cleavability 특성을 갖지 않는 링크
                    $crosslinker_all->binding_site,
                    $result[$i]['protein1_ion'],
                    $result[$i]['protein2_ion'],
                    $result[$i]['protein1_ion_mass'],
                    $result[$i]['protein2_ion_mass'],
                    $crosslinker_all->mass,// cross linker 전체의 질량값을 각 각 더해줌 (cross linker 가 절단되지 않기 때문)
                    $crosslinker_all->mass// cross linker 전체의 질량값을 각 각 더해줌 (cross linker 가 절단되지 않기 때문)
                );
            }
            $result[$i]['case']=$case;
        }
        // 8-1. 단편화된 펩타이드 (이온) 수준에 대한 모든 결과 병합
        $result_c=[];// 결과를 임시로 저장할 빈 어레이 생성
        for ($i=0;$i<count($result);$i++){// 최종 결과의 데이터 개수만큼 반복
            for ($j=0;$j<count($result[$i]['case']);$j++){// 최공 결과에서 이온 수준에 대한 데이터 개수만큼 반복
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
                ]);// 이온 수준에 대한 결과를 result_c (임시 변수)에 저장
            }
        }
        $result=$result_c;// 최종 결과 변수에 임시 변수에 저장한 내용을 덮어씀
        // 9. Modification 에 따른 경우의 수 산출
        if ($carbamidomethyl != 'N' || $oxidation != "N"){// modification에 따른 경우의 수 산출. 기본적으로 변이 정보가 선택되지 않으면 'N'값으로 둔다 / 새로운 변이 정보가 추가되면 '||' 로 조건 추가 후 아래 if문 추가
            $result_c=[];// 최종 결과 배열을 담을 빈 어레이 생성
            $chk_m_str='/[^';// 변이가 일어나는 AA 자리 문자열 체크(C,M) / 해당 문자열을 제외하고 나머지 문자열을 다 제거하는 정규표현식
            if ($carbamidomethyl != 'N'){// carbamidomethyl 변이를 체크하면
                $chk_m_str=$chk_m_str.'C';// 해당 변이가 일어나는 아미노산 자리 값을 추가 저장
            }
            if ($oxidation != 'N'){// oxidation 변이를 체크하면  /// 새로운 변이 정보를 추가하면 해당 if문을 아래 추가해줘야 함
                $chk_m_str=$chk_m_str.'M';// 해당 변이가 일어나는 아미노산 자리 값을 추가 저장
            }// 새로운 변이가 추가되면 if문을 해당 변이에 맞는 if문을 추가
            $chk_m_str=$chk_m_str.']/';// 문자열 이어 붙이기로 정규표현식 완성
            for ($i=0;$i<count($result);$i++){// 최종 결과 변수의 데이터 개수만큼 반복
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
                ];// item 변수에 최종 결과 변수 값 임시 저장
                // 변이에 해당하는 아미노산 문자열 모두가 포함되어 있는지 확인
                $protein1_peptide_str=preg_replace($chk_m_str,"",$result[$i]['protein1_peptide']);// 타겟단백질 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (C,M) 제외한 나머지 값 제거
                $protein2_peptide_str=preg_replace($chk_m_str,"",$result[$i]['protein2_peptide']);// 상호작용 단백질의 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (C,M) 제외한 나머지 값 제거
                $protein1_ion_str=preg_replace($chk_m_str,"",$result[$i]['protein1_ion']);// 타겟단백질 이온 문자열에서 변이에 해당하는 AA 문자열 (C,M) 제외한 나머지 값 제거
                $protein2_ion_str=preg_replace($chk_m_str,"",$result[$i]['protein2_ion']);// 상호작용 단백질의 이온 문자열에서 변이에 해당하는 AA 문자열 (C,M) 제외한 나머지 값 제거
                // carbamidomethyl 변이에 해당하는 아미노산 문자열이 포함되어 있는지 확인
                $protein1_peptide_c=preg_replace('/[^C]/',"",$protein1_peptide_str);// 타겟단백질 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (C) 제외한 나머지 값 제거  
                $protein2_peptide_c=preg_replace('/[^C]/',"",$protein2_peptide_str);// 상호작용 단백질의 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (C) 제외한 나머지
                $protein1_ion_c=preg_replace('/[^C]/',"",$protein1_peptide_str);// 타겟단백질 이온 문자열에서 변이에 해당하는 AA 문자열 (C) 제외한 나머지 값 제거
                $protein2_ion_c=preg_replace('/[^C]/',"",$protein2_peptide_str);// 상호작용 단백질의 이온 문자열에서 변이에 해당하는 AA 문자열 (C) 제외한 나머지 값 제거
                // oxidation 변이에 해당하는 아미노산 문자열이 포함되어 있는지 확인
                $protein1_peptide_m=preg_replace('/[^M]/',"",$protein1_peptide_str);// 타겟단백질 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (M) 제외한 나머지 값 제거  
                $protein2_peptide_m=preg_replace('/[^M]/',"",$protein2_peptide_str);// 상호작용 단백질의 펩타이드 문자열에서 변이에 해당하는 AA 문자열 (M) 제외한 나머지
                $protein1_ion_m=preg_replace('/[^M]/',"",$protein1_ion_str);// 타겟단백질 이온 문자열에서 변이에 해당하는 AA 문자열 (M) 제외한 나머지 값 제거
                $protein2_ion_m=preg_replace('/[^M]/',"",$protein2_ion_str);// 상호작용 단백질의 이온 문자열에서 변이에 해당하는 AA 문자열 (M) 제외한 나머지 값 제거
                // 각 변이에 대한 모든 경우의 수 계산
                $protein1_peptide_case=modification_all_case($protein1_peptide_str);// 타겟 단백질 펩타이드 시퀀스에서 변이가 일어나는 모든 경우의 수 계산
                $protein2_peptide_case=modification_all_case($protein2_peptide_str);// 상호작용 단백질 펩타이드 시퀀스에서 변이가 일어나는 모든 경우의 수 계산
                $protein1_ion_case=modification_all_case($protein1_ion_str);// 타겟 단백질 펩타이드 단편 (이온) 시퀀스에서 변이가 일어나는 모든 경우의 수 계산
                $protein2_ion_case=modification_all_case($protein2_ion_str);// 상호작용 단백질 펩타이드 단편 (이온) 시퀀스에서 변이가 일어나는 모든 경우의 수 계산
                // 변이에 대한 모든 경우의 수 산출
                $protein1_peptide_case_del_arr=[];// 타겟 단백질 펩타이드에서 변이 경우의 수 처리할 값을 임시 저장할 변수 생성
                $protein2_peptide_case_del_arr=[];// 상호작용 단백질 펩타이드에서 변이 경우의 수 처리할 값을 임시 저장할 변수 생성
                $protein1_ion_case_del_arr=[];// 타겟 단백질 펩타이드 단편 (이온) 에서 변이 경우의 수 처리할 값을 임시 저장할 변수 생성
                $protein2_ion_case_del_arr=[];// 상호작용 단백질 펩타이드 단편 (이온) 에서 변이 경우의 수 처리할 값을 임시 저장할 변수 생성
                for ($j=0;$j<count($protein1_peptide_case);$j++){// 타겟 단백질 펩타이드에 대한 modification 경우의 수 만큼 반복
                    $del='N';// 변이에 해당하는 아미노산 문자열을 포함할 경우 'N' 으로 설정
                    if ($carbamidomethyl=='S'){// carbamidomethyl 이 static으로 선택되고
                        if ($protein1_peptide_case[$j] != ''){// 타겟 단백질 펩타이드에서 변이 경우의 수 값이 존재하면
                            if (strpos($protein1_peptide_case[$j],$protein1_peptide_c) !== FALSE){// 아미노산 C의 위치를 발견하면 'N' 값 유지(C 아미노산이 존재하면), 포함 유무만 체크
                            } else {// 변이에 해당하는 아미노산이 존재하지 않을 경우
                                $del='Y';// 'Y' 값으로 변경
                            }
                        }
                    }
                    if ($oxidation == 'S'){// oxidation 이 static으로 선택되고
                        if ($protein1_peptide_case[$j] != ''){// 타겟 단백질 펩타이드에서 변이의 경우의 수 값이 존재하면
                            if (strpos($protein1_peptide_case[$j],$protein1_peptide_m) !== FALSE){// 아미노산 M의 위치를 발견하면 'N' 값 유지(M 아미노산이 존재하면), 포함 유무만 체크
                            } else {// 변이에 해당하는 아미노산이 존재하지 않을 경우
                                $del='Y';// 'Y' 값으로 변경
                            }
                        }
                    }
                    array_push($protein1_peptide_case_del_arr,$del);// del 변수의 값을 저장
                }
                for ($j=0;$j<count($protein1_peptide_case_del_arr);$j++){// del 변수 값을 저장한 변수의 데이터 개수만큼 반복
                    if ($protein1_peptide_case_del_arr[$j] == 'Y'){// del 변수 값이 'Y' 이면 변이에 해당하는 아미노산이 존재하지 않기 때문에
                        unset($protein1_peptide_case[$j]);// 타겟 단백질 펩타이드의 변이에 대한 모든 경우의 수 삭제 (고려하지 않음)
                    }
                }
                $protein1_peptide_case=array_values($protein1_peptide_case);// 타겟 단백질 펩타이드에 대한 변이 경우의 수 값을 반환하고 저장 (CMM 시퀀스에서 C, C, CM 과 같은 경우의 수 값)
                // 상호작용 단백질 펩타이드에 대한 변이 경우의 수 산출
                for ($j=0;$j<count($protein2_peptide_case);$j++){// 상호작용 단백질 펩타이드에 대한 변이 경우의 수 만큼 반복, 아래 코드는 위 타겟 단백질 펩타이드의 변이 코드와 동일
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
                // 타겟 단백질 펩타이드 단편 (ion) 에 대한 변이 경우의 수 산출
                for ($j=0;$j<count($protein1_ion_case);$j++){// 타겟 단백질 펩아티드 단편 (ion) 에 대한 변이 경우의 수 만큼 반복, 아래 코드는 위 타겟 단백질 펩타이드의 변이 코드와 동일
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
                // 상호작용 단백질 펩타이드 단편 (ion) 에 대한 변이 경우의 수 반복
                for ($j=0;$j<count($protein2_ion_case);$j++){// 상호작용 단백질 펩아티드 단편 (ion) 에 대한 변이 경우의 수 만큼 반복, 아래 코드는 위 타겟 단백질 펩타이드의 변이 코드와 동일
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
                // 변이 경우의 수에 따른 질량값 추가
                for ($j=0;$j<count($protein1_peptide_case);$j++){// 타겟 단백질 펩타이드의 변이 경우의 수 만큼 반복
                    $item['protein1_peptide_c_term_mass']=$result[$i]['protein1_peptide_c_term_mass'];// item 변수에 타겟 단백질에 대한 질량값을 매핑
                    $item['protein1_peptide_n_term_mass']=$result[$i]['protein1_peptide_n_term_mass'];// item 변수에 타겟 단백질에 대한 질량값을 매핑
                    $sum_p1p_c=(double)0;// 타겟 단백질 펩타이드의 변이 (cabarmidomethyl) 경우의 질량값 초기화
                    $sum_p1p_m=(double)0;// 타겟 단백질 펩타이드의 변이 (oxidation) 경우의 질량값 초기화
                    $sum_p1p_c=mb_substr_count($protein1_peptide_case[$j],'C')*(double)$modification[0]->mass;// 타겟 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                    $sum_p1p_m=mb_substr_count($protein1_peptide_case[$j],'M')*(double)$modification[1]->mass;// 타겟 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                    $item['protein1_peptide_c_term_mass']=$item['protein1_peptide_c_term_mass']+$sum_p1p_c+$sum_p1p_m;// 타겟 단백질 펩타이드 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                    $item['protein1_peptide_n_term_mass']=$item['protein1_peptide_n_term_mass']+$sum_p1p_c+$sum_p1p_m;// 타겟 단백질 펩타이드 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                    for ($k=0;$k<count($protein2_peptide_case);$k++){// 상호작용 단백질 펩타이드의 변이 경우의 수에 따른 질량값 계산
                        $item['protein2_peptide_c_term_mass']=$result[$i]['protein2_peptide_c_term_mass'];// 상호작용 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                        $item['protein2_peptide_n_term_mass']=$result[$i]['protein2_peptide_n_term_mass'];// 상호작용 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                        $sum_p2p_c=(double)0;// 상호작용 단백질 펩타이드의 변이 (cabarmidomethyl) 경우의 질량값 초기화
                        $sum_p2p_m=(double)0;// 상호작용 단백질 펩타이드의 변이 (oxidation) 경우의 질량값 초기화
                        $sum_p2p_c=mb_substr_count($protein2_peptide_case[$k],'C')*(double)$modification[0]->mass;// 상호작용 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                        $sum_p2p_m=mb_substr_count($protein2_peptide_case[$k],'M')*(double)$modification[1]->mass;// 상호작용 단백질 펩타이드 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                        $item['protein2_peptide_c_term_mass']=$item['protein2_peptide_c_term_mass']+$sum_p2p_c+$sum_p2p_m;// 상호작용 단백질 펩타이드 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                        $item['protein2_peptide_n_term_mass']=$item['protein2_peptide_n_term_mass']+$sum_p2p_c+$sum_p2p_m;// 상호작용 단백질 펩타이드 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                        for ($l=0;$l<count($protein1_ion_case);$l++){// 타겟 단백질 ion의 변이 경우의 수에 따른 질량값 계산
                            $item['protein1_ion_c_term_mass']=$result[$i]['protein1_ion_c_term_mass'];// 타겟 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                            $item['protein1_ion_n_term_mass']=$result[$i]['protein1_ion_n_term_mass'];// 타겟 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                            $sum_p1i_c=(double)0;// 타겟 단백질 펩타이드 단편 (이온) 의 변이 (cabarmidomethyl) 경우의 질량값 초기화
                            $sum_p1i_m=(double)0;// 타겟 단백질 펩타이드 단편 (이온) 의 변이 (oxidation) 경우의 질량값 초기화
                            $sum_p1i_c=mb_substr_count($protein1_ion_case[$l],'C')*(double)$modification[0]->mass;// 타겟 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                            $sum_p1i_m=mb_substr_count($protein1_ion_case[$l],'M')*(double)$modification[1]->mass;// 타겟 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                            $item['protein1_ion_c_term_mass']=$item['protein1_ion_c_term_mass']+$sum_p1i_c+$sum_p1i_m;// 타겟 단백질 펩타이드 단편 (이온) 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                            $item['protein1_ion_n_term_mass']=$item['protein1_ion_n_term_mass']+$sum_p1i_c+$sum_p1i_m;// 타겟 단백질 펩타이드 단편 (이온) 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                            for ($m=0;$m<count($protein2_ion_case);$m++){// 상호작용 단백질 ion의 변이 경우의 수에 따른 질량값 계산
                                $item['protein2_ion_c_term_mass']=$result[$i]['protein2_ion_c_term_mass'];// 상호작용 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                                $item['protein2_ion_n_term_mass']=$result[$i]['protein2_ion_n_term_mass'];// 상호작용 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                                $sum_p2i_c=(double)0;// 상호작용 단백질 펩타이드 단편 (이온) 의 변이 (cabarmidomethyl) 경우의 질량값 초기화
                                $sum_p2i_m=(double)0;// 상호작용 단백질 펩타이드 단편 (이온) 의 변이 (oxidation) 경우의 질량값 초기화
                                $sum_p2i_c=mb_substr_count($protein2_ion_case[$m],'C')*(double)$modification[0]->mass;// 상호작용 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 carbamidomethyl 에 따른 질량값을 곱해줌
                                $sum_p2i_m=mb_substr_count($protein2_ion_case[$m],'M')*(double)$modification[1]->mass;// 상호작용 단백질 펩타이드 단편 (이온) 경우의 수 산출한 결과에 C 아미노산 개수가 몇 개 있는지 확인하고, 해당 개수만큼 oxidation 에 따른 질량값을 곱해줌
                                $item['protein2_ion_c_term_mass']=$item['protein2_ion_c_term_mass']+$sum_p2i_c+$sum_p2i_m;// 상호작용 단백질 펩타이드 단편 (이온) 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                                $item['protein2_ion_n_term_mass']=$item['protein2_ion_n_term_mass']+$sum_p2i_c+$sum_p2i_m;// 상호작용 단백질 펩타이드 단편 (이온) 질량값에 위에서 산출한 변이에 따른 질량값 추가 값 합연산 (cross linker 바인딩 경우에 수에 따른 결과 마다 적용)
                                array_push($result_c, $item);// 변이에 의해 추가 산출된 질량값 및 모든 결과를 result_c 변수에 저장
                            }
                        }
                    }
                }
            }
            $result=$result_c;// result_c 변수를 result 변수 (최종 결과 변수)에 덮어씀
        }
        // 10. 최종 산출된 결과에서 전하에 따른 질량값 연산
        if (count($peptidecharge) >= 1 || count($ioncharge) >= 1){// Search 페이지에서 펩타이드 (Precursor) 전하와 이온 (Product) 전하 값이 여러개 중복 선택되면
            $result_c=(array)[];// 임시로 저장할 빈 어레이 생성
            for ($i=0;$i<count($result);$i++){// 최종 결과의 데이터 개수만큼 반복
                for ($j=0;$j<count($peptidecharge);$j++){// Search 페이지에서 선택된 펩타이드 (Precursor) 전하 개수 만큼 반복
                    for ($k=0;$k<count($ioncharge);$k++){// Search 페이지에서 선택된 이온 (Product) 전하 개수 만큼 반복
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
                        ]);// result_c 변수에 전하로 나눈 질량값 및 최종 정보 저장, 펩타이드 질량에 해당하는 값은 펩타이드 (Precursor) 전하로 나누고, 펩타이드 단편화 (이온) 질량에 해당하는 값은 이온 (Product) 전하로 나눠줌
                    }
                }
            }
            $result=$result_c;// result_c 변수를 result 변수에 덮어씀 (최종 결과가 담기는 곳은 result 변수)
        }
        // 11. Result 페이지에서 Prego 결과를 통한 결과 요약
        if ($p1_hp_peptide != '' || $p2_hp_peptide != ''){// Prego를 통한 결과 요약에 대한 입력 값이 타겟 단백질, 상호작용 단백질 모두 입력되면 
            $result_c=[];// 임시로 데이터 저장을 위한 빈 어레이 생성
            $p1_hp_peptide_arr=explode(' ',$p1_hp_peptide);// 타겟 단백질에 대한 Prego 결과 값을 복사하여 Result 페이지에 입력하면 공백을 기준으로 split하여 어레이로 변환
            $p2_hp_peptide_arr=explode(' ',$p2_hp_peptide);// 상호작용 단백질에 대한 Prego 결과 값을 복사하여 Result 페이지에 입력하면 공백을 기준으로 split하여 어레이로 변환
            for ($i=0;$i<count($result);$i++){// 최종 결과에 담긴 데이터 수 만큼 반복 (전체를 돌면서 Preogo 결과에 해당하는 펩타이드 시퀀스를 확인하기 위함)
                $chk_summary='Y';// 결과 요약을 위한 값이 입력되지 않을때 값을 'Y' 로 설정 (Prego 결과 or Combined Score 값 or 둘 다)
                for ($j=0;$j<count($p1_hp_peptide_arr);$j++){// 타겟 단백질에 대한 Prego 결과 펩타이드 개수만큼 반복
                    if (strpos($result[$i]['protein1_peptide'],$p1_hp_peptide_arr[$j]) !== FALSE){// 최종 결과에서 타겟 단백질 펩타이드에 대한 시퀀스와 Prego 결과 시퀀스를 포함하면 스킵
                    } else {// 최종 결과에서 타겟 단백질 펩타이드에 대한 시퀀스와 Prego 결과 시퀀스를 포함하지 않으면
                        $chk_summary='N';// 'Y' 값을 'N' 값으로 변환
                    }
                }
                for ($j=0;$j<count($p2_hp_peptide_arr);$j++){// 상호작용 단백질에 대한 Prego 결과 펩타이드 개수만큼 반복
                    if (strpos($result[$i]['protein2_peptide'],$p2_hp_peptide_arr[$j]) !== FALSE){// 최종 결과에서 상호작용 단백질 펩타이드에 대한 시퀀스와 Prego 결과 시퀀스를 포함하면 스킵
                    } else {// 최종 결과에서 타겟 단백질 펩타이드에 대한 시퀀스와 Prego 결과 시퀀스를  포함하지 않으면
                        $chk_summary='N';// 'Y' 값을 'N' 값으로 변환
                    }
                }
                if ($chk_summary == 'Y'){// 반약 'Y' 값일 경우
                    array_push($result_c,$result[$i]);//  Result 페이지에서 Prego의 결과 값이 포함되면 해당 값들만 새로운 변수에 덮어씀 (전체 결과에서 특정 결과만 남기는 효과)
                }
            }
            $result=$result_c;// Prego에 해당하는 값들만 최종결과 출력 변수인 result 변수에 덮어씀
        }
        // 12. Result 페이지에서 Combined Score 중 상한값 또는 하한값만 (1개의 값)만 입력하는 경우 예외처리
        if (!empty($score_min) || !empty($score_max)){// Result 페이지에서 상호작용할 확률 값을 통해 결과 요약을 위한 값이 입력되지 않으면
            $result_c=[];// 조건문 내부에서 결과를 임시로 담을 빈 어레이 생성
            for ($i=0;$i<count($result);$i++){// 최종 결과의 데이터 개수만큼 반복
                $chk_summary='Y';// 결과 요약을 위한 값이 입력되지 않을때 값을 'Y' 로 설정 (Prego 결과 or Combined Score 값 or 둘 다)
                if (!empty($score_min)){// Result 페이지에서 Combined Score 의 하한값이 입력되고
                    if ($result[$i]['combined_score']<$score_min){// 최종 결과 값에서 입력도니 Combined Score 값보다 작은 값들에 대해
                        $chk_summary='N';// 'Y' 값을 'N' 으로 변경
                    }
                }                
                if (!empty($score_max)){// Result 페이지에서 Combined Score 의 상한값 입력되고
                    if ($result[$i]['combined_score']>=$score_max){// 최종 결과 값에서 입력도니 Combined Score 값보다 큰 값들에 대해
                        $chk_summary='N';// 'Y' 값을 'N' 으로 변경
                    }
                }
                if ($chk_summary == 'Y'){// 'Y' 값일 경우
                    array_push($result_c,$result[$i]);// Result 페이지에서 Combined Score 값에 해당하는 값들만 result_c 변수에 저장
                }
            }
            $result=$result_c;// result_c 변수 값들 최종 결과를 담는 변수인 resut 변수에 덮어씀
        }
        $result=array_unique($result,SORT_REGULAR);// 최종 결과 중 모든 값이 같은 중복 데이터 제거
        $result=array_values($result);// 최종 결과 재정렬
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
            array("columns Title"=>"","Descriptions"=>"")
        );
        $columns=array(
            "Score","Protein A' Peptide","Cross Linker","Protein B' Peptide","Precursor Charge","Precursor m/z (Mass A)","Precursor m/z (Mass B)","Precursor m/z (Mass C)","Precursor m/z (Mass D)","Precursor m/z (Mass E)","Precursor m/z (Mass F)",
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