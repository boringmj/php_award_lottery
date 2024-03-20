<?php
/**
 * 奖励类
 * 
 * 相对公平的奖励抽取机制,支持抽取后用户校验奖励是否被篡改
 * 本例中,盐、奖励列表摘要等算法较为简单,实际应用中需要根据实际情况进行调整
 */
class Award {

    /**
     * 奖励列表(key:奖励,value:权重)
     * @var array
     */
    private $award_list=array(
        '奖励A'=>10,
        '奖励B'=>20,
        '奖励C'=>30,
        '奖励D'=>40
    );

    /**
     * 奖励结果列表
     * @var array
     */
    private $award_result=array();

    /**
     * 盐
     * @var string
     */
    private $salt;

    public function __construct() {
        $this->generateAward();
    }

    /**
     * 生成奖励
     * 
     * @access private
     * @param int $length 奖励列表长度
     * @return void
     */
    private function generateAward(int $length=10): void {
        $this->salt=random_bytes(16);
        $total_weight=array_sum($this->award_list);
        for($i=0;$i<$length;$i++) { 
            $rand_weight=random_int(1,$total_weight);
            $temp_weight=0;
            foreach($this->award_list as $award=>$weight) {
                $temp_weight+=$weight;
                if($rand_weight<=$temp_weight) {
                    $this->award_result[]=$award;
                    break;
                }
            }
        }
    }

    /**
     * 获取奖励
     * 
     * @access public
     * @param string $key 秘钥
     * @return array
     */
    public function getAward(string $key): array {
        $key_with_salt=$key.$this->salt;
        $key_ascii=array_map('ord',str_split($key_with_salt));
        $key_index=array_sum($key_ascii)%count($this->award_result);
        return array(
            'award'=>$this->award_result[$key_index],
            'salt'=>bin2hex($this->salt),
            'award_list'=>$this->award_result,
            'index'=>$key_index
        );
    }

    /**
     * 获取奖励列表摘要
     * 
     * @access public
     * @return string
     */
    public function getAwardListSummary(): string {
        $award_list_string=implode(',',$this->award_result).bin2hex($this->salt);
        return hash('sha256',$award_list_string);
    }

}

// 实例化奖励类
$award=new Award();
$award_summary=$award->getAwardListSummary();
// 输出奖励列表摘要
echo '摘要：'.$award_summary.PHP_EOL;
// 获取奖励
$award_info=$award->getAward('test');
// 输出奖励信息
echo '奖励：'.$award_info['award'].PHP_EOL;
echo '盐：'.$award_info['salt'].PHP_EOL;
echo '奖励列表：'.implode(',',$award_info['award_list']).PHP_EOL;
echo '索引：'.$award_info['index'].PHP_EOL;
// 校验摘要是否一致
$user_summary=hash('sha256',implode(',',$award_info['award_list']).$award_info['salt']);
echo '校验结果：'.($user_summary===$award_summary?'通过':'失败').PHP_EOL;
