<?php

namespace Install;

class htmloutput {

public function _construct() {

    }


public function gettitle() {
         return "install script by HostingBE";    
}



public function getdatabase() {

print '
<div class="row">
<div class="col-md-6">


</div>
</div>
';
}


public function getmenu() {

return array(['name' => 'algemene voorwaarden','link' => '/install/install.php?page=voorwaarden'],['name' => 'database gegevens','link' => '/install/install.php?page=database']);

}


public function header() {

print '
<!doctype html>
<html lang="en">
    <head>
    <title>'. $this->gettitle(). '</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    </head>
<body>
<div class="container">
<div class="row pt-5">
<div class="col-md-3 bg-secondary opacity">Menu
<ul class="list-unstyled">';

foreach ($this->getmenu() as $menu) {
    print '<li class="m-2"><a href="'.$menu['link'].'">'.$menu['name'].'</li>';
    }

print '</ul>
</div>
<div class="col-md-9 bg-primary">
';
    }

public function footer() {

print "
</div>
</div>
</body>
</html>
";
    }
}

?>