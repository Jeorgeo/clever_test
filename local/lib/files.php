<?

use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\Page\Asset,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Catalog\ProductTable,
    Bitrix\Sale;

Loader::includeModule('sale');
CModule::IncludeModule("catalog") ;

class FilesСlass
{

##################### Добавление файла на сервер #################################

public static function addFile($file,$base_path='',$dirIndex=0)
	{
		$text = "";
		//разрешенные для загрузки папки
		$dir = [0=>'manager', 1=>'files',2=>'logs',3=>'manager/admin_scripts'];
		if(!$dir[$dirIndex]) { return ['status'=>-1,'text'=>"Папка не разрешена для загрузки"];}
		$base_path = str_replace(['/','.'],'',$base_path);
		$base_url= $_SERVER["DOCUMENT_ROOT"].'/'.$dir[$dirIndex].($base_path ? '/'.$base_path :"");
		if(!is_dir($base_url)){return ['status'=>-1,'text'=>"Такой папки не существует : ".$base_url];}
		
		$abs_path = $base_url."/files";
		//проверяет есть ли папка, если нет, то создает
		if(!is_dir($abs_path)){
			    mkdir($abs_path,0755);
			}
	  

		if (isset($file)) {
		   // Проверяем загружен ли файл   application/vnd.ms-excel
		   if (is_uploaded_file($_FILES["file_csv"]["tmp_name"])) {
			 $info = pathinfo($_FILES['file_csv']['name']);
			 if ($info['extension'] == 'csv') {
	  
			    // Если файл загружен успешно, перемещаем его из временной директории в конечную
			    $newFileName = $abs_path.'/'.date('YmdHis').'_'.$_FILES["file_csv"]["name"];
			    move_uploaded_file($_FILES["file_csv"]["tmp_name"],  $newFileName);
			    return ['status'=>1,'text'=>"Файл загружен", 'file'=>$newFileName];
			 } else { return ['status'=>-1,'text'=>"К загрузке допущены только файлы CSV"];}
		   } else { return ['status'=>-1,'text'=>"Ошибка загрузки файла"];}
		}
		return ['status'=>-1,'text'=>"Файл не найден"];	
	}


#################################### Чтение файла #################################

public static function readFile($file,$type=1,$encod=1,$delimiter=';',$str_num=0){
$handle = fopen($file, "r");
$result = ['status'=> -1,'head'=>[],'val'=>[]];
if ($handle) { 
	$i=0; 
    while (($data = fgetcsv($handle, 4096,$delimiter)) !== false) {
		$i++;
       // if($i==$str_num){continue;}
		if($i==1 && $type==1) {foreach($data as $k=>$v){$result['head'][$i][$k]=$v;}}
		else{foreach($data as $k=>$v)
                {
                    $result['val'][$i][$k]=($encod==1 ? iconv('windows-1251', 'UTF-8', $v) : $v);
                }
            }
	 
	}
	$result['status'] = 1;
    fclose($handle);
}
return $result;
}

################################## Удаление файла #################################

}


