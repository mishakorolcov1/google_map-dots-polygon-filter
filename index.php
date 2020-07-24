<?php
    
    $postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
    if(isset($request->update_polygon) && !empty($request->update_polygon)) {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $link = mysqli_connect($servername,$username, $password, 'task'); 
        $resultTwo = mysqli_query($link, "UPDATE `gbr_new` SET `polygon`='".$request->polygon."' WHERE id_gbr =".$request->gbr_id);
        echo json_encode(array('success' => 1));
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Document</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css">
    <style>
        #map {
            height: 400px;
        }

        #type {
            height: 30px;
        }
    </style>
</head>
	

<body>

<?php    
    
$servername = "localhost";
$username = "root";
$password = "";
$link = mysqli_connect($servername,$username, $password, 'task');  
    
// var_dump($link);
$result = mysqli_query($link," SELECT  `lat`,`lng`,`field_pult_number`,`field_client`,`field_adress`,`field_summ_in_month`,`field_dogovor` ,`region`,`field_manager`,`field_contol_panel`,`debit_balance` FROM montazh_calendar WHERE  `delete` = 0 AND  `lat` != ''");
    
$polygon = mysqli_query($link,"SELECT * FROM `gbr_new`");
 



// SELECT  `lat`,`lng`,`field_pult_number`,`field_client`,`field_adress`,`field_summ_in_month`,`field_dogovor` FROM montazh_calendar WHERE  `delete` = 0 AND  `lat` != ''
//SELECT  `lat`,`lng`,`field_pult_number`,`field_client`,`field_adress`,`field_summ_in_month`,`field_dogovor`,`region` FROM montazh_calendar WHERE  `delete` = 0 AND  `lat` != ''      
    
    
$points = [];
$pointsTwo = [];
$pointsPolygon = [];
    
 while($row = mysqli_fetch_assoc($polygon)){
     if(!is_null($polygon)){
         $pointsPolygon[] = $row;
     } 
}   

while($row = mysqli_fetch_assoc($result)) {
    $points[] = $row;
    $pointsTwo[] = $row;
}
    

$points = json_encode($points);
$pointsPolygonArray = $pointsPolygon;
$pointsPolygon = json_encode($pointsPolygon);
    
?>
<div class="m-3">
<select id="type" onchange="filterChanged(event)">
    <option value="" selected="selected">Показать все</option>
    <option value="Киев">Киев</option>
    <option value="Запорожье">Запорожье</option>
    <option value="Кривой Рог">Кривой Рог</option>
    <option value="Донецк">Донецк</option>
    <option value="Днепр">Днепр</option>
    <option value="Ивано-Франковск">Ивано-Франковск</option>
    <option value="Львов">Львов</option>
</select>



    <select id="multiple-checkboxes" multiple="multiple">
    </select>
    <select name="" id="balance" onchange="balanceChanged(event)">
        <option value="" selected="selected">Показать все</option>
        <option value="-">отрицательний</option>
        <option value="+">положительний</option>
    </select> 
    
         <select id="multiple-checkbox" multiple="multiple">
    </select>   
<!--    field_contol_panel-->
<!--polygon.-->

<div id="resalt">
    
</div>







<input type="number" id="amount_from" placeholder="Мин">
<input type="number" id="amount_to" placeholder="Макс">
<input type="text" id="serch" onkeyup="search(event)" onchange="search(event)" placeholder="Поиск">
<button id="calculate" onclick="calculateAmount()">Посчитать</button>
<button id="ap" >Сохранить полигон</button>
</div>
<div id="map"></div>

<div class="container mt-4">
<!-- <button onclick='save()'>button</button>-->
<table class="table">
  <thead>
    <tr>
      <th scope="col">Номер</th>
      <th scope="col">Имя Полигона</th>
      <th scope="col">Количество точок</th>
      <th scope="col">Сумма</th>
      <th scope="col">Баланс</th>
    </tr>
  </thead>
  <tbody id="polygonTBody"></tbody>
</table>
</div>
 
<script>
    var map;
    var dotsMain = [];
    var markers = [];
    var dotsPol = [];
    var dotsAll = [];
    var dotsArray = [];
    var dotsPolygonArray = [];
    var globalPolygons = [];
    var selectedRegion = "";
    var regionAll = [
       {region: 'Киев', lat: '50.475713', lng: '30.516879'},
       {region: 'Запорожье', lat: '47.8297257', lng: '35.0783273'},
       {region: 'Кривой Рог', lat: '47.8888063', lng: '33.2742274'},
       {region: 'Донецк', lat: '47.9904918', lng: '37.705893'},
       {region: 'Днепр', lat: '48.4624412', lng: '34.9303094'},
       {region: 'Ивано-Франковск', lat: '48.9118647', lng: '24.6470889'},
       {region: 'Львов', lat: '49.8321438', lng: '23.9929112'},   
    ];
    var checkedManagers = [];
    var firstInit = true;
    var uniManeger;
    var managerSelected = [];
    
    var checkedFace = [];
    var uniFace;
    // создание полигонов
    const initMarkers = function() {
         // var dots =[{lat: 50.475713, lng: 30.516879 }]
         var dots = JSON.parse('<?=$points?>');
         var dotsPolygon = '<?=$pointsPolygon?>';
         dotsPolygon = JSON.parse(dotsPolygon.replace(/"(\[.*?\])"/g, "$1"));
         
         for (var i = 0; i < dotsPolygon.length; i++) {
             var coords = dotsPolygon[i];
             gbrPoligon = new google.maps.Polygon({
                paths: coords.polygon,
                strokeColor: '#c4c4c4',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: coords.color,
                fillOpacity: 0.35,
                Editable: true,
                draggable: true,
             });
             globalPolygons.push(gbrPoligon);
             gbrPoligon.setMap(map);

             google.maps.event.addListener(gbrPoligon.getPath(), "insert_at", getPolygonCoords.bind(null, gbrPoligon, dotsPolygon[i].id_gbr, dotsPolygon, dots));  
             gbrPoligon.addListener('click', showNumber(coords.number));
             gbrPoligon.addListener('click', getPolygonCoords.bind(null, gbrPoligon, dotsPolygon[i].id_gbr, dotsPolygon, dots));
             gbrPoligon.addListener('dragend', getPolygonCoords.bind(null, gbrPoligon, dotsPolygon[i].id_gbr, dotsPolygon, dots))
         };
        createMarkers(dots);
         
    }
    // создание карти
    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            center: {
                lat: 50.475713,
                lng: 30.516879
            },
            zoom: 10,
            mapTypeId: google.maps.MapTypeId.RoadMap
        });
        initMarkers(); 
      
    }
    //клик на полигон
    const showNumber = function(number) {
        var infoWindow = new google.maps.InfoWindow({content: number});
        return function(event) {
            
            infoWindow.setContent(number);
            infoWindow.setPosition(event.latLng);
            infoWindow.open(map);
        }
	} 
    //функция на собития полигона 
    const getPolygonCoords = function(gbrPoligon, index, dotsPolygon, dots) {
            var len = gbrPoligon.getPath().getLength(); // количество точек
            var newLatLng = [] // формируем новый пустой массив с новыми координатами точек для каждого объекта gbrPolygon
            
            //перисоздание нових точок полигона при изминение полигона
            for (var i = 0; i < len; i++) {
                newLatLng.push({
                    lat: +gbrPoligon.getPath().getAt(i).toUrlValue().split(',')[0],
                    lng: +gbrPoligon.getPath().getAt(i).toUrlValue().split(',')[1]
                });
            }
            dotsPolygon = dotsPolygon.map(dot => {
                if (dot.id_gbr === index) {
                    dot.polygon = newLatLng
                }
                return dot;
            })
            dotsPol = renderTable(dotsPolygon, dots, true);
            
            //сохрание полигона по клику            
            document.getElementById("ap").addEventListener("click",
                function(e) {
//                    e = e || window.event;
//                    var target = e.target || e.srcElement;
                    fetch('./index.php', {
                        method: 'POST',
                        headers: {
                          'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            gbr_id: index,
                            polygon: JSON.stringify(newLatLng),
                            update_polygon: true
                            })
                        }).then(res => {
                            console.log(res);
                        }) 
               }
           );     
        }
    // checking whether points fall into the polygon using the library
    const renderTable = function(dotsPolygon, dots, isMove = false) {
        if (typeof dots === "string") {
            dots = JSON.parse(dots);
        }
        let updatedDots = [];
    //библиотека на проверка точек в полигоне  
        dotsPolygon.forEach(polygon => {
            polygon.countOfMarkers = 0;
            polygon.summOfMarkers = 0;
            polygon.debit_balance = 0;
            dots.forEach(dot => {
                var result = isContainsMarker(polygon.polygon, dot.lat, dot.lng);
                if(result) {
                    updatedDots.push({...dot});
                    polygon.countOfMarkers++;
                    polygon.debit_balance += +dot.debit_balance; 
                    if (!isNaN(parseInt(dot.field_summ_in_month))) { // Если dot.field_summ_in_month является числом
                      polygon.summOfMarkers += parseInt(dot.field_summ_in_month);  
                    }
                }
            });
        }); 
        
        if (!isMove && !firstInit) {    
           setMapOnAll(null);
           createMarkers(updatedDots);  
        }
             
        document.getElementById('polygonTBody').innerHTML = '';
        let tbody = '';
        dotsPolygon.forEach(polygon => {
           tbody += `
                <tr>
                  <td>${polygon.id_gbr}</td>
                  <td>${polygon.number}</td>
                  <td>${polygon.countOfMarkers}</td>
                  <td>${polygon.summOfMarkers}</td>
                  <td>${polygon.debit_balance}</td>
                </tr>
            ` 
        });
        document.getElementById('polygonTBody').innerHTML = tbody;
        return dotsPolygon
    }
     //библиотека на проверка точек в полигоне   пересоздание полигон, точок
    const isContainsMarker = function(polygonsArray, lat, lng) {
        var coordinate = new google.maps.LatLng(lat, lng);
        
        var polygon = new google.maps.Polygon({
          paths: polygonsArray,
          strokeColor: '#FF0000',
          strokeOpacity: 0.8,
          strokeWeight: 2,
          fillColor: '#FF0000',
          fillOpacity: 0.35
        });
        return polygon.containsLatLng(coordinate);
    }
    // дописуемо значение сума и количиство точок
    window.onload = function() {
        dotsArray = JSON.parse('<?=$points?>');
        dotsPolygonArray = JSON.parse(('<?=$pointsPolygon?>').replace(/"(\[.*?\])"/g, "$1"));
        
        // checking whether points fall into the polygon itself 
        dotsPol = renderTable(dotsPolygonArray, dotsArray);
        initMultiSelect(dotsArray);
        applyFace(dotsArray);
        dotsAll = [...dotsPol];
        firstInit = false
        
    }
    
    
//    FaceChanged
///////////////////////////////////////////////////////////////////////////////////////  
    
//    var uniManeger;
//    var managerSelected = [];
// fun managerChanged
//    dotsManager
    
    
    
//    var uniFace;    
//    var checkedFace = [];
// fun  FaceChanged 
//   field_contol_panel 
//    dotsFace

    
      function FaceChanged(array) {
        var dotsFace = dotsArray.filter(dot => {
            if (array.includes(dot.field_manager)) {
                return dot
            }
        });
        if (dotsFace.length) { // if city-filter selected
            if (selectedRegion !== '') {
                dotsFace = dotsFace.filter(item => item.region === selectedRegion)
            } else {
                dotsFace = dotsArray.filter(dot => {
                    if (array.includes(dot.field_contol_panel)) {
                        return dot
                    }
                });
            }
        }
        if (dotsFace.length && dotsFace.length < uniFace.length) {
            managerSelected = [...dotsFace];
        } else {
            managerSelected = [];
        }
        // це вирізав
//        globalPolygons.forEach(g_pol => {
//            g_pol.setMap(null);
//        })
//       console.log(globalPolygons);
        
        var dotsPolygonArray = JSON.parse(('<?=$pointsPolygon?>').replace(/"(\[.*?\])"/g, "$1"));
        var newArray234 = [];
        if (document.getElementById('amount_from').value !== "" || document.getElementById('amount_to').value !== "") {
            var from = document.getElementById('amount_from').value || 0;
            var to = document.getElementById('amount_to').value || 100000;
            let rangedArray = [];
            let rangedSortedArray = [];
            dotsAll.forEach(da => {
                dotsPolygonArray.forEach(na => {
                    if (da.id_gbr === na.id_gbr) {
                        rangedArray.push(da);
                    }
                })
            })
            
            for (var i = 0; i < rangedArray.length; i++) {
               if (rangedArray[i].summOfMarkers >= parseInt(from) && rangedArray[i].summOfMarkers <= parseInt(to)) {
                    rangedSortedArray.push(rangedArray[i]);
                } 
            }
            //console.log(rangedSortedArray);
            for (let i = 0; i < [...rangedSortedArray].length; i++) {
                for (let j = 0; j < dotsFace.length; j++) {
                    if (isContainsMarker({...rangedSortedArray[i]}.polygon, dotsFace[j].lat, dotsFace[j].lng) && !newArray234.find(arr => arr.id_gbr === rangedSortedArray[i].id_gbr)) {
                        newArray234.push({...rangedSortedArray[i]});
                    }
                }
            }
            dotsPol = renderTable([...newArray234], dotsFace);
            dotsPol = renderTable(dotsPol.filter(item => item.summOfMarkers >= parseInt(from) && item.summOfMarkers <= parseInt(to)), dotsFace)
            setNewPolygons(dotsPol)
        } else {
            for (let i = 0; i < dotsPolygonArray.length; i++) {
                for (let j = 0; j < dotsFace.length; j++) {
                    if (isContainsMarker(dotsPolygonArray[i].polygon, dotsFace[j].lat, dotsFace[j].lng) && !newArray234.find(arr => arr.id_gbr === dotsPolygonArray[i].id_gbr)) {
                        newArray234.push(dotsPolygonArray[i]);
                    }
                }
            }
            dotsPol = renderTable([...newArray234], dotsFace);
            setNewPolygons([...newArray234])
        }
        if (document.getElementById('serch').value !== '') {
            search();
        }
        if (document.getElementById('balance').options[document.getElementById('balance').selectedIndex].value !== '') {
            balanceChanged(undefined, dotsPol)
        }
    }
    
    /////////////////////////////////////////////////////////////////////////////////////
    
    
    
    
    
    
    
    
    // фильтр по менеджерах  
    function managerChanged(array) {
        var dotsManager = dotsArray.filter(dot => {
            if (array.includes(dot.field_manager)) {
                return dot
            }
        });
        if (dotsMain.length) { // if city-filter selected
            if (selectedRegion !== '') {
                dotsManager = dotsManager.filter(item => item.region === selectedRegion)
            } else {
                dotsManager = dotsArray.filter(dot => {
                    if (array.includes(dot.field_manager)) {
                        return dot
                    }
                });
            }
        }
        if (dotsManager.length && dotsManager.length < uniManeger.length) {
            managerSelected = [...dotsManager];
        } else {
            managerSelected = [];
        }
        // це вирізав
//        globalPolygons.forEach(g_pol => {
//            g_pol.setMap(null);
//        })
//       console.log(globalPolygons);
        
        var dotsPolygonArray = JSON.parse(('<?=$pointsPolygon?>').replace(/"(\[.*?\])"/g, "$1"));
        var newArray234 = [];
        if (document.getElementById('amount_from').value !== "" || document.getElementById('amount_to').value !== "") {
            var from = document.getElementById('amount_from').value || 0;
            var to = document.getElementById('amount_to').value || 100000;
            let rangedArray = [];
            let rangedSortedArray = [];
            dotsAll.forEach(da => {
                dotsPolygonArray.forEach(na => {
                    if (da.id_gbr === na.id_gbr) {
                        rangedArray.push(da);
                    }
                })
            })
            
            for (var i = 0; i < rangedArray.length; i++) {
               if (rangedArray[i].summOfMarkers >= parseInt(from) && rangedArray[i].summOfMarkers <= parseInt(to)) {
                    rangedSortedArray.push(rangedArray[i]);
                } 
            }
            //console.log(rangedSortedArray);
            for (let i = 0; i < [...rangedSortedArray].length; i++) {
                for (let j = 0; j < dotsManager.length; j++) {
                    if (isContainsMarker({...rangedSortedArray[i]}.polygon, dotsManager[j].lat, dotsManager[j].lng) && !newArray234.find(arr => arr.id_gbr === rangedSortedArray[i].id_gbr)) {
                        newArray234.push({...rangedSortedArray[i]});
                    }
                }
            }
            dotsPol = renderTable([...newArray234], dotsManager);
            dotsPol = renderTable(dotsPol.filter(item => item.summOfMarkers >= parseInt(from) && item.summOfMarkers <= parseInt(to)), dotsManager)
            setNewPolygons(dotsPol)
        } else {
            for (let i = 0; i < dotsPolygonArray.length; i++) {
                for (let j = 0; j < dotsManager.length; j++) {
                    if (isContainsMarker(dotsPolygonArray[i].polygon, dotsManager[j].lat, dotsManager[j].lng) && !newArray234.find(arr => arr.id_gbr === dotsPolygonArray[i].id_gbr)) {
                        newArray234.push(dotsPolygonArray[i]);
                    }
                }
            }
            dotsPol = renderTable([...newArray234], dotsManager);
            setNewPolygons([...newArray234])
        }
        if (document.getElementById('serch').value !== '') {
            search();
        }
        if (document.getElementById('balance').options[document.getElementById('balance').selectedIndex].value !== '') {
            balanceChanged(undefined, dotsPol)
        }
    }
    
    
    
    var countFilters = 0;
//    фильтр по регионах
    function filterChanged(event) {
        if (countFilters === 2) {
            countFilters = 0;
            return
        }
        var e = document.getElementById("type");
        var value = e.options[e.selectedIndex].value;
        selectedRegion = value;
        var dotsArray = JSON.parse('<?=$points?>');
        var dotsPolygonArray = JSON.parse(('<?=$pointsPolygon?>').replace(/"(\[.*?\])"/g, "$1"));
//        проверяем если  dotsPolygonArray совбадение по велю в селекте 
        dotsPolygonArray = dotsPolygonArray.filter(polygon => polygon.region === value).length ? 
            dotsPolygonArray.filter(polygon => polygon.region === value) :
            dotsPolygonArray
        dotsMain = dotsArray.filter(dot => dot.region === value).length ? dotsArray.filter(dot => dot.region === value) : dotsArray;
        //setMapOnAll(null); // очищаем маркеры
        //createMarkers(dotsMain);
        globalPolygons.forEach(g_pol => { // очищаем полигоны
            g_pol.setMap(null);
        })
        console.log('region');
        //проверить на все и перекинуть на кординати
        if (regionAll.find(val => val.region === value)) {
            var lat = regionAll.find(val => val.region === value).lat;
            var lng = regionAll.find(val => val.region === value).lng;
            moveToLocation(lat, lng);
        } else {
            moveToLocation(50.475713, 30.516879);
        }
        //фильтр мин макс
        if (document.getElementById('amount_from').value !== "" || document.getElementById('amount_to').value !== "") {
            var from = document.getElementById('amount_from').value || 0;
            var to = document.getElementById('amount_to').value || 100000;
            var newArray234 = [];
            let rangedArray = [];
            dotsAll.forEach(da => {
                dotsPolygonArray.forEach(na => {
                    if (da.id_gbr === na.id_gbr) {
                        rangedArray.push(da);
                    }
                })
            })
            for (var i = 0; i < rangedArray.length; i++) {
               if (rangedArray[i].summOfMarkers >= parseInt(from) && rangedArray[i].summOfMarkers <= parseInt(to)) {
                    newArray234.push(rangedArray[i]);
                } 
            }
            if (managerSelected.length) {
                let filteredManager = applyManagerFilter([...newArray234], dotsMain);
                dotsPol = renderTable(filteredManager.polygons, filteredManager.dots);
                setNewPolygons(filteredManager.polygons);
                countFilters++
                filterChanged()
            } else {
                dotsPol = renderTable([...newArray234], dotsMain);
                setNewPolygons([...newArray234]);
            }
        } else {
            // if manager Selected
            if (managerSelected.length) {
                let filteredManager = applyManagerFilter(dotsPolygonArray, dotsMain);
                dotsPol = renderTable(filteredManager.polygons, filteredManager.dots);
                setNewPolygons(filteredManager.polygons);
            } else {
                dotsPol = renderTable(dotsPolygonArray, dotsMain);
                setNewPolygons(dotsPolygonArray)
            }
        }
        
        if (document.getElementById('serch').value !== '') {
            search();
        }
        
        if (document.getElementById('balance').options[document.getElementById('balance').selectedIndex].value !== '') {
            balanceChanged(undefined, dotsPol)
        }
    } 
    //фильтер баланса
    function balanceChanged(event, array = []) {
        if (!event) {
            event = {
                target: {
                    value: document.getElementById('balance').options[document.getElementById('balance').selectedIndex].value
                }
            }
        }
        if (!array.length) {
            array = [...dotsAll]
        }
        dotsMain = dotsMain.length ? dotsMain : dotsArray;
        globalPolygons.forEach(g_pol => { // очищаем полигоны
            g_pol.setMap(null);
        })
        switch (event.target.value) {
            case '+':
                dotsPol = renderTable([...array].filter(item => item.debit_balance > 0), dotsMain);
                setNewPolygons(dotsPol);
                break;
            case '-':
                dotsPol = renderTable([...array].filter(item => item.debit_balance < 0), dotsMain);
                setNewPolygons(dotsPol);
                break;
            default:
                dotsPol = renderTable([...array], dotsMain);
                setNewPolygons(dotsPol);
                break;
        }
        if (document.getElementById('serch').value !== '') {
            search();
        }
    }
    function setMapOnAll(map) {
        for (var i = 0; i < markers.length; i++) {
          markers[i].setMap(map);
        }
    }
    function showDots(contentt) {
        var infoWindow = new google.maps.InfoWindow({content: contentt});
            return function(event) {
                infoWindow.setContent(contentt);
                infoWindow.setPosition(event.latLng);
                infoWindow.open(map);
            }
        }
    //создания маркеров
    function createMarkers(dots) {
        //output points         
        for (var i = 0; i < dots.length; i++) {
            var coords = dots[i];
            var latLng = new google.maps.LatLng(coords.lat, coords.lng);
            var marker = new google.maps.Marker({
                position: latLng,
                map: map,
                icon: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                animation: google.maps.Animation.DROP,
            });
            markers.push(marker);
            var contentt= coords.field_pult_number
                + ' [' + (coords.region) + '] '                 
                + ' [' + (coords.field_client) + '] '
                + ' [' + (coords.field_adress) + '] '
                + ' [' + (coords.field_summ_in_month) + '] '
                + ' [' + (coords.field_dogovor == undefined ? '': 'cont.field_dogovor' ) + '] '
            marker.addListener('click', showDots(contentt)); 
        }
    }
    // фильри   
    var count = 0;
    //фильтр мин макс
    function calculateAmount() {
        if (count === 2) {
            count = 0;
            return
        } 
        var from = document.getElementById('amount_from').value || 0;
        var to = document.getElementById('amount_to').value || 100000;
        var newArrayRange = [];
        var dotsPolygonArray = JSON.parse(('<?=$pointsPolygon?>').replace(/"(\[.*?\])"/g, "$1"));
        for (var i = 0; i < dotsAll.length; i++) {
           if (dotsAll[i].summOfMarkers >= parseInt(from) && dotsAll[i].summOfMarkers <= parseInt(to)) {
                newArrayRange.push(dotsAll[i]);
            } 
        }
        globalPolygons.forEach(g_pol => {
            g_pol.setMap(null);
        })
        
//        applyFaceFilter  applyManagerFilter  filteredByManager filteredByFace checkedFace 
//        managerSelected checkedFace 
        if (dotsMain.length) { // if city-filter selected
            
            ////////////////////
             if (selectedRegion !== '') {
                if (checkedFace.length) {
                   let filteredByFace = applyFaceFilter([...newArrayRange].filter(item => item.summOfMarkers >= parseInt(from) && item.summOfMarkers <= parseInt(to) && item.region === selectedRegion), dotsMain);
                   dotsPol = renderTable(filteredByFace.polygons, filteredByFace.dots);
                   setNewPolygons(filteredByFace.polygons)
                   count++
                   calculateAmount();
                } else {
                  dotsPol = renderTable([...newArrayRange].filter(item => item.region === selectedRegion), dotsMain);
                  setNewPolygons([...newArrayRange].filter(item => item.region === selectedRegion))  
                }
            } else {
                if (checkedFace.length) {
                    let filteredByFace = applyFaceFilter([...newArrayRange], dotsMain);
                    dotsPol = renderTable(filteredByFace.polygons, filteredByFace.dots);
                    setNewPolygons(filteredByFace.polygons)
                } else {
                    dotsPol = renderTable([...newArrayRange], dotsMain);
                    setNewPolygons([...newArrayRange])
                }}
            
            ////////////////
            if (selectedRegion !== '') {
                if (managerSelected.length) {
                   let filteredByManager = applyManagerFilter([...newArrayRange].filter(item => item.summOfMarkers >= parseInt(from) && item.summOfMarkers <= parseInt(to) && item.region === selectedRegion), dotsMain);
                   dotsPol = renderTable(filteredByManager.polygons, filteredByManager.dots);
                   setNewPolygons(filteredByManager.polygons)
                   count++
                   calculateAmount();
                } else {
                  dotsPol = renderTable([...newArrayRange].filter(item => item.region === selectedRegion), dotsMain);
                  setNewPolygons([...newArrayRange].filter(item => item.region === selectedRegion))  
                }
            } else {
                if (managerSelected.length) {
                    let filteredByManager = applyManagerFilter([...newArrayRange], dotsMain);
                    dotsPol = renderTable(filteredByManager.polygons, filteredByManager.dots);
                    setNewPolygons(filteredByManager.polygons)
                } else {
                    dotsPol = renderTable([...newArrayRange], dotsMain);
                    setNewPolygons([...newArrayRange])
                }
            }
        } else {
            
              if (checkedFace.length) {
                let filteredByFace = applyFaceFilter([...newArrayRange], dotsArray);
                dotsPol = renderTable(filteredByFace.polygons, filteredByFace.dots);
                setNewPolygons(filteredByFace.polygons)
                count++
                calculateAmount();
            } else {
                dotsPol = renderTable([...newArrayRange], dotsArray);
                setNewPolygons([...newArrayRange])
            }
            
            
            if (managerSelected.length) {
                let filteredByManager = applyManagerFilter([...newArrayRange], dotsArray);
                dotsPol = renderTable(filteredByManager.polygons, filteredByManager.dots);
                setNewPolygons(filteredByManager.polygons)
                count++
                calculateAmount();
            } else {
                dotsPol = renderTable([...newArrayRange], dotsArray);
                setNewPolygons([...newArrayRange])
            }
        }
        if (document.getElementById('serch').value !== '') {
            search();
        }
        if (document.getElementById('balance').options[document.getElementById('balance').selectedIndex].value !== '') {
            balanceChanged(undefined, dotsPol)
        }
        
    }  
    function moveToLocation(lat, lng){
        const center = new google.maps.LatLng(lat, lng);
        map.panTo(center);
    }
    //пошук
    function search(event) {
        if (!event) {
            event = {
                target: {
                    value: document.getElementById('serch').value
                }
            }
        }
        document.getElementById('resalt').innerHTML = '';
        dotsMain = dotsMain.length ? dotsMain : dotsArray;
        markers.forEach(marker => {
            marker.setIcon('http://maps.google.com/mapfiles/ms/icons/red-dot.png');
        })
        console.log(dotsMain);
        var tempArray = dotsMain.filter(dot => {
            return dot.field_pult_number.indexOf(event.target.value) !== -1 || dot.field_dogovor.indexOf(event.target.value) !== -1 || dot.field_client.indexOf(event.target.value) !== -1  
        })
        tempArray = tempArray.length === dotsArray.length ? [] : tempArray
        if (!tempArray.length) {
            document.getElementById('resalt').innerHTML = 'не нашло';
            console.log('не нашло');
        }
        if (tempArray.length === 1) {
            setTimeout(function() {
                moveToLocation(tempArray[0].lat, tempArray[0].lng);
                map.setZoom(14);
            }, 1000)
        } else {
            if (selectedRegion.length && selectedRegion !== 'all') {
                var lat = regionAll.find(val => val.region === selectedRegion).lat;
                var lng = regionAll.find(val => val.region === selectedRegion).lng;
                moveToLocation(lat, lng);
                map.setZoom(10);
            } else {
                map.setZoom(10);
            }
        }
        if (event.target.value !== "") {
            markers.forEach(marker => {
                tempArray.forEach(temp => {
                    if (+temp.lat === +marker.getPosition().lat() && +temp.lng === +marker.getPosition().lng()) {
                        marker.setIcon('http://maps.google.com/mapfiles/ms/icons/green-dot.png');
                    } 
                })
            })
        } else {
             document.getElementById('resalt').innerHTML = '';
        }
    }
    
    function setNewPolygons(array) {
        ////////////////////////////////перерисовка   полигонов ghb 
        for (var i = 0; i < array.length; i++) {
            var coords = array[i];
            gbrPoligon = new google.maps.Polygon({
              paths: coords.polygon,
              strokeColor: '#c4c4c4',
              strokeOpacity: 0.8,
              strokeWeight: 2,
              fillColor: coords.color,
              fillOpacity: 0.35,
              Editable: true,
              draggable: true,
            });
            gbrPoligon.setMap(map);
            globalPolygons.push(gbrPoligon);
            google.maps.event.addListener(gbrPoligon.getPath(), "insert_at", getPolygonCoords.bind(null, gbrPoligon, array[i].id_gbr, dotsPol, dotsMain));  
            gbrPoligon.addListener('click', showNumber(coords.number));
            gbrPoligon.addListener('dragend', getPolygonCoords.bind(null, gbrPoligon, array[i].id_gbr, dotsPol, dotsMain))
        };
        ////////////////////////////////
    }
    //менеджери библиотека на основі бустрап 3 фильтер
     /////////////////////////////////////////////////////////////////////////////////////////////    
    function initMultiSelect(dots) {
       
        document.getElementById('multiple-checkboxes').innerHTML = '';
        let sbody = '';
        uniManeger = [...new Set(dots.map(item => item.field_manager))];
        uniManeger.forEach(maneger => {
           sbody += `<option value="${maneger}">${maneger}</option>` 
        });
        document.getElementById('multiple-checkboxes').innerHTML = sbody;
        $('#multiple-checkboxes').multiselect({
          includeSelectAllOption: true,
          buttonText: function(options, select) {
            if (options.length === uniManeger.length) {
                checkedManagers = [];
                for(let i = 0; i < uniManeger.length; i++) {
                    checkedManagers.push(uniManeger[i])
                }
                managerChanged(checkedManagers);
            } else if (!options.length) {
                if (!firstInit) {
                    checkedManagers = uniManeger;
                    managerChanged(checkedManagers);
                }   
            }
              
            if (options.length) {
                return `вибрано ${options.length} менеджера(ів)`
            } else {
                return 'не вибрано менеджера';
            }
            
          },
          onChange: function(option, checked, select) {
            if (checked) {
                console.log('test 1');
                if (checkedManagers.length === uniManeger.length) {
                    checkedManagers = [];
                }
                checkedManagers.push(option[0].value);
            } else {
                console.log('test 2');
                if (checkedManagers.filter(manager => manager !== option[0].value)) {
                    checkedManagers = checkedManagers.filter(manager => manager !== option[0].value);
                } else {
                    checkedManagers = uniManeger
                } 
            } 
            managerChanged(checkedManagers);
          }
        });

        
    }
    

  //3 фильтер менеджер  
    function applyManagerFilter(polygons, dots) {
        console.log(polygons);
        let pols = [...polygons];
        let tempDots = [];
        dots.forEach(dm => {
            if (!tempDots.find(td => td.field_manager === dm.field_manager)) {
                managerSelected.forEach(ms => {
                    if (dm.field_manager === ms.field_manager) {
                        tempDots.push({...ms});
                    }
                })
            }
        })
    
        var tempPolygons = [];
        for (let i = 0; i < [...pols].length; i++) {
            for (let j = 0; j < tempDots.length; j++) {
                if (isContainsMarker([...pols][i].polygon, tempDots[j].lat, tempDots[j].lng) && ![...tempPolygons].find(arr => arr.id_gbr === [...pols][i].id_gbr)) {
                    tempPolygons.push([...pols][i]);
                }
            }
        }
        
        console.log([...tempPolygons]);
        return {
            polygons: [...tempPolygons],
            dots: [...tempDots]
        }    
    }
///////////////////////////////////////////////////////////////////////////////////////////////    
    
    
//    var uniManeger;
//    var managerSelected = [];
// fun managerChanged
    
    
//    var uniFace;    
//    var checkedFace = [];
//dotsAll
// fun  FaceChanged 
    
 ////////////////////////////////////4 фильтер checkedFace  checkedFace = []
     
   function applyFace(dots){
//        field_contol_panel
        document.getElementById('multiple-checkbox').innerHTML = '';
        let mbody = '';
        uniFace = [...new Set(dots.map(item => item.field_contol_panel))];
        uniFace.forEach(maneger => {
           mbody += `<option value="${maneger}">${maneger}</option>` 
        });
//         console.log(mbody);
        document.getElementById('multiple-checkbox').innerHTML = mbody;
        $('#multiple-checkbox').multiselect({
          includeSelectAllOption: true,
          buttonText: function(options, select) {
            if (options.length === uniFace.length) {
                checkedFace = [];
                for(let i = 0; i < uniFace.length; i++) {
                    checkedManagers.push(uniFace[i])
                }
                FaceChanged(checkedFace);
            } else if (!options.length) {
                if (!firstInit) {
                    checkedFace = uniFace;
                    FaceChanged(uniFace);
                }   
            }
              
            if (options.length) {
                return `вибрано ${options.length} Юридическое лицо`
            } else {
                return 'не вибрано Юридическое лицо ';
            }
            
          },
          onChange: function(option, checked, select) {
            if (checked) {
                 console.log('test 11');
                if (checkedFace.length === uniFace.length) {
                    checkedFace = [];
                }
                checkedFace.push(option[0].value);
            } else {
                 console.log('test 22');
                if (checkedFace.filter(manager => manager !== option[0].value)) {
                    checkedFace = checkedFace.filter(manager => manager !== option[0].value);
                } else {
                    checkedFace = uniFace
                } 
            } 
            FaceChanged(checkedFace);
          }
        }); 
   } 
    
//field_contol_panel field_manager
    
    function applyFaceFilter(polygons, dots) {
        console.log(polygons);
        let pols = [...polygons];
        let tempDots = [];
        dots.forEach(dm => {
            if (!tempDots.find(td => td.field_manager === dm.field_contol_panel)) {
                checkedFace.forEach(ms => {
                    if (dm.field_contol_panel === ms.field_contol_panel) {
                        tempDots.push({...ms});
                    }
                })
            }
        })
    
        var tempPolygons = [];
        for (let i = 0; i < [...pols].length; i++) {
            for (let j = 0; j < tempDots.length; j++) {
                if (isContainsMarker([...pols][i].polygon, tempDots[j].lat, tempDots[j].lng) && ![...tempPolygons].find(arr => arr.id_gbr === [...pols][i].id_gbr)) {
                    tempPolygons.push([...pols][i]);
                }
            }
        }
        
        console.log([...tempPolygons]);
        return {
            polygons: [...tempPolygons],
            dots: [...tempDots]
        }    
    }    
    
    
    
    
</script>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&amp;sensor=false&key=AIzaSyDyAnMcgQ4TqGttyFKA5hxWYxIkzZpo3V8&callback=initMap" async defer></script>
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<!--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>-->
 <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js"></script>
   
<script src="https://github.com/tparkin/Google-Maps-Point-in-Polygon"></script>
<script>

if (!google.maps.Polygon.prototype.getBounds) {
  google.maps.Polygon.prototype.getBounds = function(latLng) {
    var bounds = new google.maps.LatLngBounds(),
      paths = this.getPaths(),
      path,
      p, i;

    for (p = 0; p < paths.getLength(); p++) {
      path = paths.getAt(p);
      for (i = 0; i < path.getLength(); i++) {
        bounds.extend(path.getAt(i));
      }
    }

    return bounds;
  };
}
// Polygon containsLatLng - method to determine if a latLng is within a polygon
google.maps.Polygon.prototype.containsLatLng = function(latLng) {
  // Exclude points outside of bounds as there is no way they are in the poly

  var inPoly = false,
    bounds, lat, lng,
    numPaths, p, path, numPoints,
    i, j, vertex1, vertex2;

  // Arguments are a pair of lat, lng variables
  if (arguments.length == 2) {
    if (
      typeof arguments[0] == "number" &&
      typeof arguments[1] == "number"
    ) {
      lat = arguments[0];
      lng = arguments[1];
    }
  } else if (arguments.length == 1) {
    bounds = this.getBounds();

    if (!bounds && !bounds.contains(latLng)) {
      return false;
    }
    lat = latLng.lat();
    lng = latLng.lng();
  } else {
  }

  // Raycast point in polygon method

  numPaths = this.getPaths().getLength();
  for (p = 0; p < numPaths; p++) {
    path = this.getPaths().getAt(p);
    numPoints = path.getLength();
    j = numPoints - 1;

    for (i = 0; i < numPoints; i++) {
      vertex1 = path.getAt(i);
      vertex2 = path.getAt(j);

      if (
        vertex1.lng() <  lng &&
        vertex2.lng() >= lng ||
        vertex2.lng() <  lng &&
        vertex1.lng() >= lng
      ) {
        if (
          vertex1.lat() +
          (lng - vertex1.lng()) /
          (vertex2.lng() - vertex1.lng()) *
          (vertex2.lat() - vertex1.lat()) <
          lat
        ) {
          inPoly = !inPoly;
        }
      }

      j = i;
    }
  }

  return inPoly;
};
</script>
</body>
</html>


