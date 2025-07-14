<?
include './version.php';
include_once(__DIR__ . '/../auth/index.php');include_once(__DIR__ . '/../install/config.php');
;
// 获取当前访问域名（不含协议和端口）

function update_version(){ //在线更新代码
    include './version.php';
    $app_uid = '1'; //你的应用UID
    $query = xx_get_curl("https://auth.moeres.cn/check.php?url=".$_SERVER["HTTP_HOST"]."&authcode=".authcode."&ver=".VERSION."&app_uid=".$app_uid);
    $query = json_decode($query, true);
    if(is_array($query)){
    if ($query = json_decode(xx_authcode(base64_decode($query['data']), 'DECODE', 'p4M1zMpedIipUnAeEz8ZEeec3UKuc83r'),true)) {
        return $query;
        }
    }
    return false;
}

$title='检查版本更新';

//函数
function zipExtract ($src, $dest)
{
$zip = new ZipArchive();
if ($zip->open($src)===true)
{
$zip->extractTo($dest);
$zip->close();
return true;
}
return false;
}
function deldir($dir) {
  if(!is_dir($dir))return false;
  $dh=opendir($dir);
  while ($file=readdir($dh)) {
    if($file!="." && $file!="..") {
      $fullpath=$dir."/".$file;
      if(!is_dir($fullpath)) {
          unlink($fullpath);
      } else {
          deldir($fullpath);
      }
    }
  }
  closedir($dh);
  if(rmdir($dir)) {
    return true;
  } else {
    return false;
  }
}

$scriptpath=str_replace('\\','/',$_SERVER['SCRIPT_NAME']);
$scriptpath = substr($scriptpath, 0, strrpos($scriptpath, '/'));
$admin_path = substr($scriptpath, strrpos($scriptpath, '/')+1);
?>
<div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-sync-alt"></i> 检查更新
        </div>
        <div class="card-body">
            <?php
            $act = isset($_GET['act']) ? $_GET['act'] : null;

            if ($act == 'do') {
                $ret=update_version();
                $RemoteFile = $ret['file'];
                $ZipFile = "Archive.zip";
                if(copy($RemoteFile,$ZipFile)) {
                    if (zipExtract($ZipFile,'../')) {
                        if(function_exists("opcache_reset"))@opcache_reset();
                        $addstr = '';
                        if(!empty($ret['sql'])){ // 如果变量 $res 中的 sql 非空
                            $sql=$ret['sql']; // 将 $res['sql'] 赋值给 $sql 变量
                            $t=0; $e=0; $error=''; // 初始化成功句数 $t 为 0，失败句数 $e 为 0，错误信息 $error 为空字符串
                            for($i=0;$i<count($sql);$i++) { // 循环遍历 $sql 数组中的每一条语句
                                if (trim($sql[$i])=='')continue; // 如果当前语句为空字符串，则跳过当前循环
                                if($conn->query($sql[$i])) { // 如果当前语句执行成功
                                    ++$t; // 成功句数 $t 自增 1
                                } else { // 如果当前语句执行失败
                                    ++$e; // 失败句数 $e 自增 1
                                    $error.=$conn->error().'<br/>'; // 将错误信息拼接到 $error 变量中
                                }
                            }
                            $addstr='<br/>数据库更新成功。SQL成功'.$t.'句/失败'.$e.'句！<br>注：失败是数据库已经存在这个字段，所以不用管！'; // 将执行结果拼接成字符串，并赋值给 $addstr 变量
                        }

                        echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> 程序更新成功！".$addstr."<br><a href='/'>返回首页</a></div>";
                        unlink($ZipFile);
                    } else {
                        echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> 无法解压文件！<br><a href='/admin/page=update'>返回上级</a></div>";
                        if (file_exists($ZipFile))
                            unlink($ZipFile);
                    }
                } else {
                    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> 无法下载更新包文件！<a href='/admin/page=update'>返回上级</a></div>";
                }
            } else {
                $ret=update_version();

                if(!$ret['msg'])$ret['msg']='啊哦，更新服务器开小差了，请刷新此页面。';
                include './version.php';
                ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> <span class="text-success"><?php echo $ret['msg']; ?></span><br/>当前版本：<?php echo(VER)?></div><hr/>
                <?php
                if($ret['code']==1) {

                    if(!class_exists('ZipArchive') || defined("SAE_ACCESSKEY") || defined("BAE_ENV_APPID")) {
                        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> 您的空间不支持自动更新，请手动下载更新包并覆盖到程序根目录！<br/>
                        更新包下载：<a href="'.$ret['file'].'" class="btn btn-primary"><i class="fas fa-download"></i> 点击下载</a></div>';
                    } else {
                        echo '<a href="/admin/?page=update&act=do" class="btn btn-success btn-block"><i class="fas fa-arrow-up"></i> 立即更新到最新版本</a>';
                    }
                    ?>
                    <?php if (is_array($ret['ver'])): ?>
                        <?php foreach ($ret['ver'] as $index => $ver): ?>
                            <hr/>
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $ver; ?> [<?php echo $ret['version'][$index]; ?>]</h5>
                                    <p class="card-text"><?php echo $ret['uplog'][$index]; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <hr/>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $ret['ver']; ?> [<?php echo $ret['version']; ?>]</h5>
                                <p class="card-text"><?php echo $ret['uplog']; ?></p>
                            </div>
                        </div>
                    <?php endif;
                }
            }
            ?>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
.card {
    border: 1px solid #e9ecef;
    margin-bottom: 15px;
    border-radius: 5px;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
}

.card-header {
    padding: 0.75rem 1.25rem;
    margin-bottom: 0;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;
}

.card-title {
    margin-bottom: 0.75rem;
}

.card-body {
    padding: 1.25rem;
}

.alert {
    border-radius: 5px;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
}

.alert-info {
    color: #0c5460;
    background-color: #d1ecf1;
    border-color: #bee5eb;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-warning {
    color: #856404;
    background-color: #fff3cd;
    border-color: #ffeeba;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.btn-primary {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}

.btn-primary:hover {
    color: #fff;
    background-color: #0069d9;
    border-color: #0062cc;
}

.btn-success {
    color: #fff;
    background-color: #28a745;
    border-color: #28a745;
}

.btn-success:hover {
    color: #fff;
    background-color: #218838;
    border-color: #1e7e34;
}

.btn-block {
    display: block;
    width: 100%;
}

hr {
    margin-top: 1rem;
    margin-bottom: 1rem;
    border: 0;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}
</style>

            </div>
        </div>
    </div>
</div>
