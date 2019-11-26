<?php

ini_set('log_errors','on');  //ログを取得
ini_set('error_log','php.log');  //ログの出力ファイルを指定
session_start(); //セッションスタート

// 自分のHP
// define("MY_HP", 500);
// モンスター格納用配列
$monsters = array();
// クラスの作成
abstract class Character{
  // プロパティ
  protected $name;
  protected $hp;
  // protected $img;\\
  protected $attackMin;
  protected $attackMax;
  public function setName($str){
    $this->name = $str;
  }
  public function getName(){
    return $this->name;
  }
  public function setHp($num){
    $this->hp = $num;
  }
  public function getHp(){
    return $this->hp;
  }
  // メソッド
  public function attack($target){
    $attackPoint = mt_rand($this->attackMin, $this->attackMax);
    if(!mt_rand(0,9)){ //10分の1の確率でモンスターのクリティカル
      $attackPoint *= 1.5;
      $attackPoint = (int)$attackPoint;
      $_SESSION['history'] .= $this->getName().'の<br>クリティカルヒット!!<br>';
    }
    $target->setHp($target->getHp() - $attackPoint);
    $_SESSION['history'] .= $attackPoint.'ポイントのダメージ！<br>';
  }
}
// プレイヤークラス
class Player extends Character{
  public function __construct($name, $hp, $attackMin, $attackMax) {
    $this->name = $name;
    $this->hp = $hp;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
  }
}
// モンスタークラス
class Monster extends Character{
  // プロパティ
  protected $img;
  // コンストラクタ
  public function __construct($name, $hp, $img, $attackMin, $attackMax) {
    $this->name = $name;
    $this->hp = $hp;
    $this->img = $img;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
  }
  // ゲッター
  public function getImg(){
    return $this->img;
  }
}

// 魔法を使えるモンスタークラス
class MagicMonster extends Monster{
  private $magicAttack;
  function __construct($name, $hp, $img, $attackMin, $attackMax, $magicAttack) {
    // 親クラスのコンストラクタで処理する内容を継承したい場合には親コンストラクタを呼び出す。
    parent::__construct($name, $hp, $img, $attackMin, $attackMax);
    $this->magicAttack = $magicAttack;
  }
  public function getMagicAttack(){
    return $this->magicAttack;
  }
  // attackメソッドをオーバーライドすることで、「ゲーム進行を管理する処理側」は単にattackメソッドを呼べばいいだけになる
  // 魔法を使えるモンスターは、自分で魔法を出すか普通に攻撃するかを判断する
  public function attack($target){
    if(!mt_rand(0,4)){ //5分の1の確率で魔法攻撃
      $_SESSION['history'] .= $this->name.'の魔法攻撃!!<br>';
      $target->setHp($target->getHp() - $this->magicAttack);
      $_SESSION['history'] .= $this->magicAttack.'ポイントのダメージを受けた！<br>';
    }else{
      // 通常の攻撃の場合は、親クラスの攻撃メソッドを使うことで、親クラスの攻撃メソッドが修正されてもMagicMonsterでも反映される
      parent::attack($target);
    }
  }
}

// インスタンス生成
$player = new Player('勇者', 500, 40, 120);
$monsters[] = new Monster( 'スライム', 100, 'img/monster01.png', 10, 30 );
$monsters[] = new MagicMonster( 'パンプキン', 200, 'img/monster02.png', 20, 40, mt_rand(10, 20) );
$monsters[] = new Monster( 'ゴブリン', 200, 'img/monster03.png', 20, 50 );
$monsters[] = new Monster( 'サーベルウルフ', 250, 'img/monster04.png', 20, 60 );
$monsters[] = new Monster( 'デスフィッシュ', 150, 'img/monster05.png', 30, 50 );
$monsters[] = new MagicMonster( '魔法使い', 200, 'img/monster06.png', 10, 20, mt_rand(40, 60) );
$monsters[] = new MagicMonster( 'マジックブック', 150, 'img/monster07.png', 10, 30, mt_rand(60, 80) );
$monsters[] = new MagicMonster( 'カオスドラゴン', 400, 'img/monster08.png', 30, 50, mt_rand(60, 100) );
$monsters[] = new Monster( 'オラフ', 180, 'img/monster09.png', 30, 50 );
$monsters[] = new MagicMonster( 'アザゼル', 350, 'img/monster10.png', 40, 50, mt_rand(80, 120));

function createMonster(){
  global $monsters;
  $monster =  $monsters[mt_rand(0, 9)];
  $_SESSION['history'] .= $monster->getName().'が現れた！<br>';
  $_SESSION['monster'] =  $monster;
}
function createPlayer(){
  global $player;
  $_SESSION['player'] =  $player;
}
function init(){
  $_SESSION['history'] .= '初期化します！<br>';
  $_SESSION['knockDownCount'] = 0;
  createPlayer();
  createMonster();
}
function gameOver(){
  $_SESSION = array();
}


// post送信されていた場合
if(!empty($_POST)){
  $attackFlg = (!empty($_POST['attack'])) ? true : false;
  $startFlg = (!empty($_POST['start'])) ? true : false;
  error_log('POSTされた！');
  
  if($startFlg){
    $_SESSION['history'] = 'ゲームスタート！<br>';
    init();
  }else{
    // 攻撃するを押した場合
    if($attackFlg){
    // モンスターに攻撃を与える
      $_SESSION['history'] .= $_SESSION['player']->getName().'の攻撃！';

      $_SESSION['player']->attack($_SESSION['monster']);
      
      // モンスターが攻撃をする
      $_SESSION['history'] .= $_SESSION['monster']->getName().'の攻撃！' ;
      $_SESSION['monster']->attack($_SESSION['player']);
      
      // 自分のhpが0以下になったらゲームオーバー
      if($_SESSION['player']->getHp() <= 0){
        gameOver();
      }else{
        // hpが0以下になったら、別のモンスターを出現させる
        if($_SESSION['monster']->getHp() <= 0){
          $_SESSION['history'] .= $_SESSION['monster']->getName().'を倒した！<br><br>';
          createMonster();
          $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
        }
      }
    }else{ //逃げるを押した場合
      $_SESSION['history'] .= '逃げることに成功！<br>';
      createMonster();
    }
  }
  $_POST = array();
}

?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>SWORD QUEST</title>
    <link rel="stylesheet" type="text/css" href="style.css">
  </head>
  <body>
   <h1 style="text-align:center; color:#333;"></h1>
    <div class="bg" style=position:relative;>
      <?php if(empty($_SESSION)){ ?>
        <h2>SWORD QUEST</h2>
        <form class="gamestart" method="post">
          <input type="submit" name="start" value="▶ゲームスタート">
        </form>
      <?php }else{ ?>
         <p class="top-win"><?php echo $_SESSION['monster']->getName().'が現れた!!'; ?></p>
        <div style="height: 150px;">
          <img src="<?php echo $_SESSION['monster']->getImg(); ?>" style="width:180px; height:auto; margin: 0 auto; display:block;">
        </div>
        <div class="slash" style="display: none;">  
         <!-- style="display: none;"  -->
        <img src="img/slash.png">
      </div>
        <p class="monster-hp">モンスターのHP：<?php echo $_SESSION['monster']->getHp(); ?></p>
        <div class="window">
        <p class="left-win">倒したモンスター数：<?php echo $_SESSION['knockDownCount']; ?><br>勇者の残りHP： <?php echo $_SESSION['player']->getHp(); ?></p>
    <!--     <p class="right-win"><?php// echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p> -->
        </div>
        <form  class="command" method="post">
          <input type="submit" name="attack" value="▶攻撃する">
          <input type="submit" name="escape" value="▶逃げる">
          <input type="submit" name="start" value="▶ゲームリスタート">
        </form>
      <?php } ?>
      <div class="right-win">
        <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
      </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="main.js"></script>

  </body>
</html>
