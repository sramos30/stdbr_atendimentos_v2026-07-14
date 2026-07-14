import './style.css';
import numeral from 'numeral';

var szCurrentLocale = 'pt-br';

numeral.register('locale', szCurrentLocale, {
    delimiters: {
        thousands: '.',
        decimal: ','
    },
    abbreviations: {
        thousand: 'mil',
        million: 'milhões',
        billion: 'b',
        trillion: 't'
    },
    ordinal: function(number) {
        return 'º';
    },
    currency: {
        symbol: 'R$'
    }
});

var szCurrentLocale = 'pt-br';

var objTerminaisStatus = 'loading';
var objProdutosStatus = 'loading';
var objAtendimento = {};
var savedObjAtendimento = {};

var atdId = 0;
var readOnly = false;

var objTerminais = {};
var objTerminaisTags = {};

var objProdutos = {};
var objProdutosTags = {};

var objDcp = {};
var objExcel = {};

function parseDate(d) {
    let day = 0;
    let month = 0;
    let year = 0;

    let pattern = /(\d{1,2})[\-/ \.](\d{1,2})[\-/ \.](\d{4})/;
    let patternIso = /(\d{4})[\-/ \.](\d{1,2})[\-/ \.](\d{1,2})/;

    let result = d.match(pattern);

    //console.log( "d:", d, "result:", result );

    if (null != result && result.length > 3) {
        day = Number(result[1]);
        month = Number(result[2]);
        year = Number(result[3]);

        if (year < 1000)
            year += 2000;
    } else {
        result = d.match(patternIso);

        if (null != result && result.length > 3) {
            year = Number(result[1]);
            month = Number(result[2]);
            day = Number(result[3]);
        }
    }

    if ((day < 1 || day > 31) ||
        (month < 1 || month > 12) ||
        (year < 1970 || year > 2500))
        return null;

    return new Date(year, month - 1, day);
}

//https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
function getParameterByName(name, url = window.location.href) {
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

function getOutrasPartesOption(objAtendimento, fSelected = false, value) {
    let optNode = document.createElement('OPTION');
    optNode.value = value;
    optNode.selected = fSelected;
    optNode.innerHTML = value;
    return optNode;
}

function writeToConsole(text = "") {
    let divConsole = document.getElementById("writeToconsole");

    if (null != divConsole) {
        divConsole.innerText = text;
    }

    if (text.length > 0) {
        console.log("writeToConsole:", divConsole, `text: "${text}"`);
        // clear the console after showing a message for 10s
        setTimeout(writeToConsole, 10000, '');
    }
}

function onUpdateBalancaArqueacao() {
    let faltaExcesso = (parseFloat(objAtendimento.arqueacao) - objAtendimento.balanca);

    if (faltaExcesso > 0) {
        objAtendimento.excesso = faltaExcesso.toFixed(3);
        objAtendimento.falta = "0.000";
    } else {
        //faltaExcesso = -1 * faltaExcesso;
        objAtendimento.falta = (-1 * faltaExcesso).toFixed(3);
        objAtendimento.excesso = "0.000";
    }

    objAtendimento.diferenca = ((faltaExcesso / objAtendimento.balanca) * 100).toFixed(2);
    fillGroup1();
}

function onChangeForm1(evt) {
    let fldName = evt.target.name.toUpperCase();
    let fldValue = evt.target.value;

    let pattern = /^([A-Z]+)_?(\d*)_?(\d*)/;
    let fldBaseName = fldName.match(pattern);

    writeToConsole();

    //==>debug
    //console.log("onChangeForm1 => fldName:", fldName, "fldBaseName:", fldBaseName, "fldValue:", fldValue);

    switch (fldBaseName[1]) {
        case "CODATENDIMENTO":
            {
                objAtendimento.codAtendimento = fldValue.toUpperCase();
                evt.target.value = fldValue;
                break;
            }
        case "DATA":
            {
                //
                //node.addEventListener("focusout", (evt) => {
                let data = parseDate(evt.target.value);

                //console.log( 'onChange: data:', data, ' objAtendimento.data:', objAtendimento.data  );

                if (data == null)
                    evt.target.focus();
                else {
                    evt.target.value = new Date(data).toLocaleDateString(szCurrentLocale);

                    objAtendimento.data = data.toISOString().substring(0, 10);

                    //console.log( 'onChange:  evt.target.value:',  evt.target.value, ' objAtendimento.data:', objAtendimento.data  );

                    //console.log( ' evt.target.value:',  evt.target.value  );
                }

                //});
                break;
            }
        case "NAVIO":
            {
                evt.target.value = fldValue;
                objAtendimento.navio = fldValue;
                break;
            }
        case "BALANCA":
            {
                let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                evt.target.value = numeral(value).format('0,0.000');

                if (Number(objAtendimento.balanca) != Number(value)) {
                    objAtendimento.balanca = value.toFixed(3);
                    onUpdateBalancaArqueacao();
                }
                break;
            }
        case "ARQUEACAO":
            {
                let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                evt.target.value = numeral(value).format('0,0.000');

                if (Number(objAtendimento.arqueacao) != Number(value)) {
                    objAtendimento.arqueacao = value.toFixed(3);
                    onUpdateBalancaArqueacao();
                }
                break;
            }
        case "COMANDONAVIO":
            {
                let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                evt.target.value = numeral(value).format('0,0.000');

                if (Number(objAtendimento.comando_navio) != Number(value))
                    objAtendimento.comando_navio = value.toFixed(3);
                break;
            }
        case "PERITORECEITA":
            {
                let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                evt.target.value = numeral(value).format('0,0.000');

                if (Number(objAtendimento.perito_receita) != Number(value))
                    objAtendimento.perito_receita = value.toFixed(3);
                break;
            }
        case "OUTRASPARTES":
            {
                let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                evt.target.value = numeral(value).format('0,0.000');

                switch (fldName) {
                    case "OUTRASPARTES_1":
                        {
                            if (Number(objAtendimento.outras_partes1) != Number(value))
                                objAtendimento.outras_partes1 = value.toFixed(3);
                            break;
                        }
                    case "OUTRASPARTES_2":
                        {
                            if (Number(objAtendimento.outras_partes2) != Number(value))
                                objAtendimento.outras_partes2 = value.toFixed(3);
                            break;
                        }
                    case "OUTRASPARTES_3":
                        {
                            if (Number(objAtendimento.outras_partes3) != Number(value))
                                objAtendimento.outras_partes3 = value.toFixed(3);
                            break;
                        }
                }
                break;
            }
        case "OUTRASPARTESID":
            {
                evt.target.value = fldValue;

                switch (fldName) {
                    case "OUTRASPARTESID_1":
                        {
                            objAtendimento.outras_partes1_id = fldValue;
                            break;
                        }
                    case "OUTRASPARTESID_2":
                        {
                            objAtendimento.outras_partes2_id = fldValue;
                            break;
                        }
                    case "OUTRASPARTESID_3":
                        {
                            objAtendimento.outras_partes3_id = fldValue;
                            break;
                        }
                }
                break;
            }
        case "PORAOSELECT":
            {
                //console.log("fldName:", fldName, "fldValue:", fldValue, "length:", evt.target.options.length);

                for (let i = 0; i < evt.target.options.length; i++) {
                    if (evt.target.options[i].selected) {

                        let pattern = /^([A-Za-z]+)_?(\d*)_?(\d*)/;
                        let porProd = evt.target.options[i].name.match(pattern);

                        //console.log("porProd", porProd, "objAtendimento.poroes", objAtendimento.poroes);

                        if (!objAtendimento.poroes)
                            objAtendimento.poroes = {};

                        if (!objAtendimento.poroes[porProd[2]])
                            objAtendimento.poroes[porProd[2]] = {
                                "produto_id": 0,
                                "terminais": {},
                                "cubagem": 0,
                                "fatorestiva": 0.00,
                                "condicao": "---"
                            };

                        objAtendimento.poroes[porProd[2]].produto_id = porProd[3];
                    }
                    //console.log( "name:", evt.target.options[i].name, "id:", evt.target.options[i].id,  "value:", evt.target.options[i].value);
                }
                break;
            }
        case "QTDPT":
            {
                let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));

                //console.log("fldName:", fldName, "fldValue:", fldValue, "fldBaseName", fldBaseName);

                if (!objAtendimento.poroes)
                    objAtendimento.poroes = {};

                if (!objAtendimento.poroes[fldBaseName[2]])
                    objAtendimento.poroes[fldBaseName[2]] = {
                        "produto_id": 0,
                        "terminais": {},
                        "cubagem": 0,
                        "fatorestiva": 0.00,
                        "condicao": "---"
                    };

                if (!objAtendimento.poroes[fldBaseName[2]].terminais)
                    objAtendimento.poroes[fldBaseName[2]].terminais = {};

                if (Number(value) != Number(objAtendimento.poroes[fldBaseName[2]].terminais[fldBaseName[3]])) {
                    objAtendimento.poroes[fldBaseName[2]].terminais[fldBaseName[3]] = {
                        "quantidade": value.toFixed(3)
                    };
                }

                fillDcpTable();

                break;
            }
        case "CUBPORAO":
            {
                let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));

                //==>debug
                //console.log("fldName:", fldName, "fldValue:", fldValue, "fldBaseName", fldBaseName, "objAtendimento.poroes:", objAtendimento.poroes);

                if (!("poroes" in objAtendimento))
                    objAtendimento.poroes = {};

                if (!(fldBaseName[2] in objAtendimento.poroes))
                    objAtendimento.poroes[fldBaseName[2]] = {
                        "produto_id": 0,
                        "terminais": {},
                        "cubagem": 0,
                        "fatorestiva": 0.00,
                        "condicao": "---"
                    };

                //console.log(`savedObjAtendimento.poroes[${fldBaseName[2]}].cubagem:`, savedObjAtendimento.poroes[fldBaseName[2]].cubagem);

                objAtendimento.poroes[fldBaseName[2]].cubagem = value;

                //==>debug
                //console.log(`savedObjAtendimento.poroes[${fldBaseName[2]}].cubagem:`, savedObjAtendimento.poroes[fldBaseName[2]].cubagem);

                fillDcpTable();

                break;
            }
        case "COND":
            {
                //==>debug
                //console.log("fldName:", fldName, "fldValue:", fldValue, "fldBaseName", fldBaseName, "objAtendimento.poroes:", objAtendimento.poroes);

                if (!("poroes" in objAtendimento))
                    objAtendimento.poroes = {};

                if (!(fldBaseName[2] in objAtendimento.poroes))
                    objAtendimento.poroes[fldBaseName[2]] = {
                        "produto_id": 0,
                        "terminais": {},
                        "cubagem": 0,
                        "fatorestiva": 0.00,
                        "condicao": "---"
                    };

                //console.log(`savedObjAtendimento.poroes[${fldBaseName[2]}].condicao:`, savedObjAtendimento.poroes[fldBaseName[2]].condicao);

                objAtendimento.poroes[fldBaseName[2]].condicao = fldValue;

                //==>debug
                //console.log(`savedObjAtendimento.poroes[${fldBaseName[2]}].condicao:`, savedObjAtendimento.poroes[fldBaseName[2]].condicao);

                fillDcpTable();

                break;
            }
        default:
            {
                let className = evt.target.className.toUpperCase();

                //==>debug
                //console.log("onChangeForm1 => fldName:", fldName, "fldBaseName:", fldBaseName, "fldValue:", fldValue, "className:", className, "evt.target", evt.target);

                switch (className) {
                    case "CBPOROES":
                        {
                            let cbPoroes = document.querySelectorAll(".cbPoroes");

                            objAtendimento.lstPoroes = [];

                            if (!("poroes" in objAtendimento))
                                objAtendimento.poroes = {};

                            let prodId = (objAtendimento.lstProdutos.length == 1) ? objAtendimento.lstProdutos[0] : "0";

                            cbPoroes.forEach(i => {

                                //console.log("i:", i);

                                if (i.checked) {

                                    if (!(i.id in objAtendimento.poroes)) {
                                        objAtendimento.poroes[i.id] = {
                                            "produto_id": prodId,
                                            "terminais": {},
                                            "cubagem": 0,
                                            "fatorestiva": 0.00,
                                            "condicao": "---"
                                        };
                                    }

                                    objAtendimento.lstPoroes.push(i.value);
                                }
                            });

                            //console.log("objAtendimento.lstPoroes", objAtendimento.lstPoroes);
                            //console.log("objAtendimento.poroes", objAtendimento.poroes);

                            fillDcpTable();
                            break;
                        }
                    case "CBTERMINAIS":
                        {
                            let cbTerminais = document.querySelectorAll(".cbTerminais");

                            //console.log( 'cbTerminais:', cbTerminais );
                            //console.log( 'objAtendimento.lstTerminais:', objAtendimento.lstTerminais );
                            //console.log( 'objAtendimento.terminais:', objAtendimento.terminais );

                            objAtendimento.lstTerminais = [];

                            if (!("terminais" in objAtendimento))
                                objAtendimento.terminais = [];

                            cbTerminais.forEach(i => {
                                if (i.checked) {
                                    if (!objAtendimento.terminais.includes(Number(i.id))) {
                                        objAtendimento.terminais.push(Number(i.id));
                                    }

                                    objAtendimento.lstTerminais.push(Number(i.id));
                                }
                            });

                            //console.log( 'objAtendimento.lstTerminais:', objAtendimento.lstTerminais );
                            //console.log( 'objAtendimento.terminais:', objAtendimento.terminais );

                            fillDcpTable();
                            break;
                        }
                    case "CBPRODUTOS":
                        {
                            let cbProdutos = document.querySelectorAll(".cbProdutos");

                            //console.log( 'cbProdutos:', cbProdutos );
                            //console.log( 'objAtendimento.lstProdutos:', objAtendimento.lstProdutos );
                            //console.log( 'objAtendimento.produtos:', objAtendimento.produtos );

                            objAtendimento.lstProdutos = [];

                            cbProdutos.forEach(i => {
                                if (i.checked) {
                                    
                                    //if (!objAtendimento.produtos[i.id]) {
                                    //    objAtendimento.produtos[i.id] = JSON.parse(JSON.stringify(objProdutos[i.id]));
                                    //      { ...objProdutos[i.id]};
                                    //}

                                    if (!objAtendimento.produtos.includes(Number(i.id))) {
                                        objAtendimento.produtos.push(Number(i.id));
                                    }
    
                                    objAtendimento.lstProdutos.push(Number(i.id));
                                }
                            });

                            //console.log( 'objAtendimento.lstProdutos:', objAtendimento.lstProdutos );
                            //console.log( 'objAtendimento.produtos:', objAtendimento.produtos );

                            fillDcpTable();
                            break;
                        }
                    case "CBDCPFILES":
                        {
                            //let cbDcpFiles = document.querySelectorAll(".cbDcpFiles");
                            //
                            //objAtendimento.dcpFileToDelete = [];
                            //
                            //cbDcpFiles.forEach(i => {
                            //    if (i.checked) {
                            //        objAtendimento.dcpFileToDelete.push(i.id);
                            //    }
                            //});

                            //console.log("objAtendimento", objAtendimento);
                            cbdFilesChanged();
                            break;
                        }

                }
                break;
            }
    }
}

function cbdFilesChanged() {
    let bt = document.getElementById("fileDelete");
    let cbDcpFiles = document.querySelectorAll(".cbDcpFiles");

    objAtendimento.dcpFileToDelete = [];

    cbDcpFiles.forEach(i => {
        if (i.checked) {
            objAtendimento.dcpFileToDelete.push(i.id);
        }
    });

    if (objAtendimento.dcpFileToDelete.length > 0) {
        bt.disabled = false;
    } else {
        bt.disabled = true;
    }
}

function onReloadDcpXls(ext) {
    if (objTerminaisStatus.substring(0, 6) == "Loaded" &&
        objProdutosStatus.substring(0, 6) == "Loaded") {

        disableButton(false);
        
        //==>debug
        console.log(`onReloadDcpXls(${ext})`);

        let headers = new Headers();
        headers.append('Accept', 'application/json');

        fetch(`/atendimentos/api/showexcel.php?mode=json&filename=${ext}`, {
                mode: 'cors', // no-cors, *cors, same-origin
                method: "GET", // *GET, POST, PUT, DELETE, etc.
                headers: headers,
                cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                credentials: 'same-origin', // include, *same-origin, omit
                redirect: 'follow', // manual, *follow, error
                referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            })
            .then(response => {
                //console.error("response:", response);
                
                return response.json();
            })
            .then(json => {
                objExcel = JSON.parse(JSON.stringify(json));

                //console.log("objExcel:", objExcel);

                objDcp = convExcel2Json(objExcel);
                updateJsonAtd(objDcp);

                //==> debug
                console.log("objDcp(onReloadDcpXls):", objDcp);
                
                setAtendimentoDcp(objAtendimento, objDcp);

                //console.log("objAtendimento(onReloadDcpXls):", objAtendimento);

                fillGroup1();
                fillDcpTable();
            })
            .catch(err => {
                let error = `catch: fetch onReloadDcpXls: ${err.message}`;
                writeToConsole(error);
            })
    }
}

// onLoadDcpFiles()
function onLoadDcpFiles() {

    const files = document.getElementById("planodecarga").files;
    
    if (Object.keys(files).length > 0) {
        if (Number(atdId) > 0) disableButton(false);

        (document.getElementById("processDcpFile")).disabled = false;
    } else {
        (document.getElementById("processDcpFile")).disabled = true;
    }

    // Object.keys(files).forEach(i => {
    //     let file = files[i];

    //     if (file.name.indexOf("xls") > 0) {

    //         if (objTerminaisStatus.substring(0, 6) == "Loaded" &&
    //             objProdutosStatus.substring(0, 6) == "Loaded") {

    //             let formData = new FormData();
    //             formData.append("file", file);

    //             let headers = new Headers();
    //             headers.append('Accept', 'application/json');

    //             fetch(`/atendimentos/api/showexcel.php?mode=json`, {
    //                     mode: 'cors', // no-cors, *cors, same-origin
    //                     method: "POST", // *GET, POST, PUT, DELETE, etc.
    //                     body: formData,
    //                     headers: headers,
    //                     cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
    //                     credentials: 'same-origin', // include, *same-origin, omit
    //                     redirect: 'follow', // manual, *follow, error
    //                     referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
    //                 })
    //                 .then(response => {
    //                     return response.json();
    //                 })
    //                 .then(json => {
    //                     objExcel = JSON.parse(JSON.stringify(json));

    //                     //console.log('excel:', objExcel);

    //                     objDcp = convExcel2Json(objExcel);

    //                     //objDcp["atdId"] = "New";

    //                     updateJsonAtd(objDcp);

    //                     setAtendimentoDcp(objAtendimento, objDcp);

    //                     //console.log("objAtendimento(onLoadDcpFiles):", objAtendimento);

    //                     fillGroup1();
    //                     fillDcpTable();

    //                     return objDcp;
    //                 })
    //                 .catch(err => {
    //                     let error = `catch: fetch objDcp: ${err.message}, ${JSON.stringify(err)}`;
    //                     writeToConsole(error);
    //                 })
    //         }
    //     }
    // })
}

function onProcessDcpFiles() {
    const files = document.getElementById("planodecarga").files;

    Object.keys(files).forEach(i => {
        let file = files[i];

        if (file.name.indexOf("xls") > 0) {

            if (objTerminaisStatus.substring(0, 6) == "Loaded" &&
                objProdutosStatus.substring(0, 6) == "Loaded") {

                let formData = new FormData();
                formData.append("file", file);

                let headers = new Headers();
                headers.append('Accept', 'application/json');

                fetch(`/atendimentos/api/showexcel.php?mode=json`, {
                        mode: 'cors', // no-cors, *cors, same-origin
                        method: "POST", // *GET, POST, PUT, DELETE, etc.
                        body: formData,
                        headers: headers,
                        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                        credentials: 'same-origin', // include, *same-origin, omit
                        redirect: 'follow', // manual, *follow, error
                        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
                    })
                    .then(response => {
                        return response.json();
                    })
                    .then(json => {
                        objExcel = JSON.parse(JSON.stringify(json));
                        objDcp = convExcel2Json(objExcel);
                        
                        //==>debug
                        console.log( 'onProcessDcpFiles(objDcp):', objDcp);
                        
                        updateJsonAtd(objDcp);
                        setAtendimentoDcp(objAtendimento, objDcp);

                        fillGroup1();
                        fillDcpTable();

                        return objDcp;
                    })
                    .catch(err => {
                        let error = `catch: fetch objDcp: ${err.message}, ${JSON.stringify(err)}`;
                        writeToConsole(error);
                    })
            }
        }
    });
}

// Main App
function crateMainApp() {
    const mainApp = document.getElementById("MainApp");
    mainApp.innerText = "";

    // title
    //let divTitle = document.createElement("DIV");
    //
    //if ( readOnly == false ) {
    //    divTitle.innerText = "ATENDIMENTOS - LISTAR";
    //}else 
    //if (atdId == "New") {
    //    divTitle.innerText = "ATENDIMENTOS - CRIAR NOVO";
    //} else 
    //{
    //    divTitle.innerText = "ATENDIMENTOS - EDITAR";
    //}
    //
    //mainApp.appendChild(divTitle);

    //<form id="form1" onsubmit="return onSubmitForm1(event);">
    const frmForm1 = document.createElement("FORM");
    frmForm1.id = "form1";
    frmForm1.enctype = "multipart/form-data";

    //frmForm1.onsubmit = function(evt) {
    //    return onSubmitForm1(evt);
    //};

    if (readOnly == false) {
        frmForm1.onchange = function(evt) {
            return onChangeForm1(evt);
        };
    }

    //Atendimento Id
    let divNode = document.createElement("DIV");
    divNode.innerText = `Atendimento Id: ${atdId>0?atdId:'New'}`
    frmForm1.appendChild(divNode);

    if (readOnly == false) {
        // Plano de carga detalhado
        let divNode = document.createElement("DIV");
        divNode.innerHTML = '<label for="planodecarga">Adicionar Plano de Carga Detalhado:</label>';
        
        let node = document.createElement("INPUT");
        node.name = "planodecarga";
        node.type = "file";
        node.id = "planodecarga";
        node.size = "30";
        node.multiple = true;
        node.accept = ".pdf, .xls, .xlsx, .xlsm";

        node.onchange = function() {
            return onLoadDcpFiles();
        };

        divNode.appendChild(node);

        node = document.createElement("INPUT");
        node.type = "button";
        node.id = "processDcpFile";
        node.disabled = true;
        node.value = "processa DCP";

        node.onclick = function(evt) {
            onProcessDcpFiles(evt);
        };

        divNode.appendChild(node);

        frmForm1.appendChild(divNode);

        if (objAtendimento.lstPlanos) {
            // load saved dcp files
            let divNode = document.createElement("DIV");
            let ctdItems = 0;
            objAtendimento.lstPlanos.forEach(ext => {
                if (ext.toUpperCase().substring(0, 3) == "XLS") {
                    let node = document.createElement('INPUT');
                    node.type = "button";
                    node.id = `${objAtendimento.atdId}.${ext}`;
                    node.value = `load DCP (${ext})`;
                    node.onclick = function(evt) {
                        //console.log('button onclick', evt.target);
                        onReloadDcpXls(evt.target.id);
                    };
                    ctdItems += 1;
                    divNode.appendChild(node);
                }
            });
            
            if (ctdItems > 0) {
                frmForm1.appendChild(divNode);
            }
        }
        // Remove saved dcp files
        divNode = document.createElement("DIV");
        divNode.id = "cbDcpFiles";
        frmForm1.appendChild(divNode);

        // button
        node = document.createElement("INPUT");
        node.type = "button";
        node.id = "salvar";
        node.disabled = false;

        if (atdId && Number(atdId) > 0)
            node.value = "Save";
        else
            node.value = "Add New";

        node.onclick = function(evt) {
            onSaveForm1(evt);
        };

        frmForm1.appendChild(node);

        //<p id="writeToconsole"></p>
        let pNode = document.createElement("P");
        pNode.id = "writeToconsole";

        frmForm1.appendChild(pNode);
    }

    // Group 1
    let fldSet = document.createElement("fieldset");
    fldSet.id = "fldSetfillGroup1";
    //fldSet.oninput = function (evt) { return onInputGroup1(evt); };
    //fldSet.onchange = function (evt) { return onChangeGroup1(evt); };

    divNode = document.createElement("DIV");
    divNode.id = "group1";

    fldSet.appendChild(divNode);
    frmForm1.appendChild(fldSet);

    // dcp table
    fldSet = document.createElement("fieldset");
    fldSet.id = "fldSetDcpTable";
    //fldSet.onChange = function (evt) { return onChangeDcp(evt); };

    divNode = document.createElement("DIV");
    divNode.id = "dcpTable";

    fldSet.appendChild(divNode);
    frmForm1.appendChild(fldSet);

    mainApp.appendChild(frmForm1);
}

function fillDeleteFileList() {
    const divNode = document.getElementById("cbDcpFiles");

    if (readOnly == false) {
        if (objAtendimento.lstPlanos) {
            divNode.innerHTML = `<label for="dcpFileToDelete">Delete files:</label>`

            let ctdFiles = 0;

            objAtendimento.lstPlanos.forEach(ext => {
                let node = document.createElement('INPUT');
                node.type = "checkbox";
                node.name = `dcpFileToDelete`;
                node.id = ext;
                node.value = ext;
                node.className = "cbDcpFiles";

                divNode.appendChild(node);
                divNode.append(ext);
            });

            // button
            let node = document.createElement("INPUT");
            node.type = "button";
            node.id = "fileDelete";
            node.disabled = true;
            node.value = "Delete";

            node.onclick = function(evt) {
                onDeleteFile(evt);
            };

            divNode.appendChild(node);
        }

    }
}

function fillGroup1() {
    //==>debug
    //console.log('fillGroup1() objAtendimento:', objAtendimento, ' readOnly:', readOnly);

    try {
        let divGroup1 = document.getElementById("group1");
        divGroup1.innerHTML = "";

        //<input name="codAtendimento" type="text" id="codAtendimento" size="15" maxlength="20" />
        let divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="codAtendimento">Cod.Atendimento:</label>`

        let node = document.createElement("INPUT");
        node.name = "codAtendimento";
        node.type = "text";
        node.id = "codAtendimento";
        node.size = "15";
        node.maxlength = "20";
        node.value = objAtendimento.codAtendimento;
        node.readOnly = readOnly;
        node.oninput = function(evt) {
            evt.target.value = evt.target.value.toUpperCase();
        };

        divNode.appendChild(node);
        divGroup1.appendChild(divNode);

        //<input name="data" type="text" id="data" placeholder="dd/mm/yyyy" size="10" maxlength="10" required/></td >
        divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="data">Data:</label>`
        node = document.createElement("INPUT");
        node.name = "data";
        node.type = "text";
        node.id = "data";
        node.size = "10";
        node.maxlength = "10";
        node.readOnly = readOnly;

        //node.value = objAtendimento.data.toLocaleDateString(szCurrentLocale);
        node.value = new Date(Number(objAtendimento.data.substring(0, 4)), Number(objAtendimento.data.substring(5, 7)) - 1, Number(objAtendimento.data.substring(8, 10))).toLocaleDateString(szCurrentLocale);
        divNode.appendChild(node);
        divGroup1.appendChild(divNode);

        //<input name="navio" type="text" id="navio"  size="30" maxlength="80" required/>
        divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="navio">Navio:</label>`
        node = document.createElement("INPUT");
        node.name = "navio";
        node.type = "text";
        node.id = "navio";
        node.size = "30";
        node.maxlength = "80";
        node.readOnly = readOnly;

        node.value = objAtendimento.navio;

        divNode.appendChild(node);
        divGroup1.appendChild(divNode);

        //<input name="balanca" type="text" id="balanca" placeholder="0.00" size="13" required />
        divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="balanca">Carregado por balança:</label>`
        node = document.createElement("INPUT");
        node.name = "balanca";
        node.type = "text";
        node.id = "balanca";
        node.size = "13";
        node.readOnly = readOnly;

        node.value = numeral(Number(objAtendimento.balanca)).format('0,0.000');

        divNode.appendChild(node);
        divGroup1.appendChild(divNode);

        //<input name="arqueacao" type="text" id="arqueacao" placeholder="0.00" size="13" required />
        divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="arqueacao">Carregado por arqueação:</label>`
        node = document.createElement("INPUT");
        node.name = "arqueacao";
        node.type = "text";
        node.id = "arqueacao";
        node.size = "13";
        node.readOnly = readOnly;

        node.value = numeral(Number(objAtendimento.arqueacao)).format('0,0.000');

        divNode.appendChild(node);
        divGroup1.appendChild(divNode);

        //<input name="comando_navio" type="text" id="comando_navio" placeholder="0.00" size="13" required/>
        divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="comando_navio">Comando Navio:</label>`
        node = document.createElement("INPUT");
        node.name = "comandonavio";
        node.type = "text";
        node.id = "comandonavio";
        node.size = "13";
        node.readOnly = readOnly;

        node.value = numeral(Number(objAtendimento.comando_navio)).format('0,0.000')

        divNode.appendChild(node);
        divGroup1.appendChild(divNode);

        //<input name="perito_receita" type="text" id="perito_receita" placeholder="0.00" size="13" required/>
        divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="perito_receita">Perito Receita:</label>`
        node = document.createElement("INPUT");
        node.name = "peritoreceita";
        node.type = "text";
        node.id = "peritoreceita";
        node.size = "13";
        node.readOnly = readOnly;

        node.value = numeral(Number(objAtendimento.perito_receita)).format('0,0.000')

        divNode.appendChild(node);
        divGroup1.appendChild(divNode);

        //<div id="outras_partes1_cblist"></div>
        divNode = document.createElement("DIV");
        divNode.id = "outrasPartesCblist_1";
        divNode.innerHTML = '<label for="outrasPartes_1">Outras Partes 1:</label>';

        let outrasPartesValue = Number(objAtendimento.outras_partes1);

        if (outrasPartesValue == 0)
            objAtendimento.outras_partes1_id = "---";

        node = document.createElement("INPUT");
        node.value = numeral(outrasPartesValue).format('0,0.000')

        node.type = "text";
        node.id = "outrasPartes_1";
        node.name = "outrasPartes_1";
        node.size = "13";
        node.readOnly = readOnly;

        divNode.appendChild(node);

        if (readOnly == false) {
            node = document.createElement('SELECT');
            node.name = 'outrasPartesId_1';
            node.id = 'outrasPartesId_1';

            node.appendChild(getOutrasPartesOption(objAtendimento, false, "---"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes1_id == 'Exportador'), "Exportador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes1_id == 'Armador'), "Armador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes1_id == 'P&I Armador'), "P&I Armador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes1_id == 'Afretador'), "Afretador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes1_id == 'P&I Afretador'), "P&I Afretador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes1_id == 'Comprador'), "Comprador"));

            divNode.appendChild(node);
        } else {
            node = document.createElement("INPUT");
            node.name = "outrasPartesId_1";
            node.type = "text";
            node.id = "outrasPartesId_1";
            node.size = "13";
            node.readOnly = true;
            node.value = objAtendimento.outras_partes1_id;

            divNode.appendChild(node);
        }

        divGroup1.appendChild(divNode);

        //<div id="outras_partes2_cblist"></div>
        divNode = document.createElement("DIV");
        divNode.id = "outrasPartesCblist_2";
        divNode.innerHTML = '<label for="outrasPartes_2">Outras Partes 2:</label>';

        outrasPartesValue = Number(objAtendimento.outras_partes2);

        if (outrasPartesValue == 0)
            objAtendimento.outras_partes2_id = "---";

        node = document.createElement("INPUT");

        node.value = numeral(outrasPartesValue).format('0,0.000')

        node.type = "text";
        node.id = "outrasPartes_2";
        node.name = "outrasPartes_2";
        node.size = "13";
        divNode.appendChild(node);

        if (readOnly == false) {
            node = document.createElement('SELECT');
            node.name = 'outrasPartesId_2';
            node.id = 'outrasPartesId_2';
            node.readOnly = readOnly;

            node.appendChild(getOutrasPartesOption(objAtendimento, false, "---"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes2_id == 'Exportador'), "Exportador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes2_id == 'Armador'), "Armador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes2_id == 'P&I Armador'), "P&I Armador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes2_id == 'Afretador'), "Afretador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes2_id == 'P&I Afretador'), "P&I Afretador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes2_id == 'Comprador'), "Comprador"));
            divNode.appendChild(node);
        } else {
            node = document.createElement("INPUT");
            node.name = "outrasPartesId_2";
            node.type = "text";
            node.id = "outrasPartesId_2";
            node.size = "13";
            node.readOnly = true;
            node.value = objAtendimento.outras_partes2_id;

            divNode.appendChild(node);
        }

        divGroup1.appendChild(divNode);

        //<div id="outras_partes3_cblist"></div>
        divNode = document.createElement("DIV");
        divNode.id = "outrasPartesCblist_3";
        divNode.innerHTML = '<label for="outrasPartes_3">Outras Partes 3:</label>';

        outrasPartesValue = Number(objAtendimento.outras_partes3);

        if (outrasPartesValue == 0)
            objAtendimento.outras_partes3_id = "---";

        node = document.createElement("INPUT");

        node.value = numeral(outrasPartesValue).format('0,0.000')

        node.type = "text";
        node.id = "outrasPartes_3";
        node.name = "outrasPartes_3";
        node.size = "13";
        divNode.appendChild(node);

        if (readOnly == false) {
            node = document.createElement('SELECT');
            node.name = 'outrasPartesId_3';
            node.id = 'outrasPartesId_3';

            node.appendChild(getOutrasPartesOption(objAtendimento, false, "---"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes3_id == 'Exportador'), "Exportador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes3_id == 'Armador'), "Armador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes3_id == 'P&I Armador'), "P&I Armador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes3_id == 'Afretador'), "Afretador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes3_id == 'P&I Afretador'), "P&I Afretador"));
            node.appendChild(getOutrasPartesOption(objAtendimento, (outrasPartesValue && objAtendimento.outras_partes3_id == 'Comprador'), "Comprador"));
            divNode.appendChild(node);
        } else {
            node = document.createElement("INPUT");
            node.name = "outrasPartesId_2=3";
            node.type = "text";
            node.id = "outrasPartesId_3";
            node.size = "13";
            node.readOnly = true;
            node.value = objAtendimento.outras_partes3_id;

            divNode.appendChild(node);
        }

        divGroup1.appendChild(divNode);

        //<input name="excesso" type="text" id="excesso" placeholder="0.000" size="13" required />
        divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="excesso">Excesso:</label>`
        node = document.createElement("INPUT");
        node.name = "excesso";
        node.type = "text";
        node.id = "excesso";
        node.size = "13";
        node.readOnly = true;
        node.value = numeral(Number(objAtendimento.excesso)).format('0,0.000');
        divNode.appendChild(node);
        divGroup1.appendChild(divNode);

        //<input name="falta" type="text" id="falta" placeholder="0.000" size="13" required />
        divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="falta">Falta:</label>`
        node = document.createElement("INPUT");
        node.name = "falta";
        node.type = "text";
        node.id = "falta";
        node.size = "13";
        node.readOnly = true;
        node.value = numeral(Number(objAtendimento.falta)).format('0,0.000');
        divNode.appendChild(node);
        divGroup1.appendChild(divNode);

        //<input name="diferenca" type="text" id="diferenca" placeholder="0.00" size="13" required />
        divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="falta">Diferença (%):</label>`
        node = document.createElement("INPUT");
        node.name = "diferenca";
        node.type = "text";
        node.id = "diferenca";
        node.size = "13";
        node.readOnly = true;
        node.value = numeral(Number(objAtendimento.diferenca)).format('0.00');
        divNode.appendChild(node);
        divGroup1.appendChild(divNode);

        if (readOnly == false) {

            //<div id="poroes_checkbox"></div>
            divNode = document.createElement("DIV");
            divNode.id = "poroesCheckbox";
            divNode.name = "poroesCheckbox";
            //<input type="checkbox" name="poroesId" id="1" value="1" />1
            divNode.innerHTML = '<label for="poroesId">Por&otilde;es:</label>';

            for (let p = 1; p <= 9; p++) {
                let value = `${p}`;
                const node = document.createElement('INPUT');
                node.type = "checkbox";
                node.name = `poroesId_${value}`;
                node.id = value;
                node.value = value;
                node.className = "cbPoroes";

                //==>debug
                //console.log('fillGroup1(cbPoroes) value:', value, 
                //    'Number(value):', objAtendimento.lstPoroes.includes(Number(value)),
                //    'String(value):', objAtendimento.lstPoroes.includes(String(value)),
                //    );

                if (objAtendimento.lstPoroes.includes(String(value)) || 
                    objAtendimento.lstPoroes.includes(Number(value)) )
                    node.checked = true;
                else
                    node.checked = false;

                divNode.appendChild(node);
                divNode.append(value);
            };

            divGroup1.appendChild(divNode);

            //<div id="produtos_checkbox"></div>
            divNode = document.createElement("DIV");
            divNode.id = "produtosCheckbox";
            divNode.innerHTML = '<label for="produtosId">Produtos:</label>';

            //==>debug
            //console.log('fillGroup1(cbProdutos) objAtendimento.lstProdutos:', objAtendimento.lstProdutos);

            Object.keys(objProdutos).forEach( value => {
                const node = document.createElement('INPUT');
                node.type = "checkbox";
                node.name = `produtosId_${value}`;
                node.id = value;
                node.value = objProdutos[value].nome;
                node.className = "cbProdutos";

                //==>debug
                //console.log('fillGroup1(cbProdutos) value:', value, 
                //    'Number(value):', objAtendimento.lstProdutos.includes(Number(value)),
                //    'String(value):', objAtendimento.lstProdutos.includes(String(value)),
                //    );

                if (objAtendimento.lstProdutos.includes(Number(value)) ||
                    objAtendimento.lstProdutos.includes(String(value)) ) {
                    node.checked = true;
                } else {
                    node.checked = false;
                }

                divNode.appendChild(node);
                divNode.append(objProdutos[value].nome);
            });

            divGroup1.appendChild(divNode);

            //<div id="terminais_checkbox"></div>
            divNode = document.createElement("DIV");
            divNode.id = "terminaisCheckbox";
            divNode.innerHTML = '<label for="terminaisId">Terminais:</label>';

            //==>debug
            //console.log('fillGroup1(cbTerminais) objAtendimento.lstTerminais:', objAtendimento.lstTerminais);

            Object.keys(objTerminais).forEach(value => {
                const node = document.createElement('INPUT');
                node.type = "checkbox";
                node.name = `terminaisId_${value}`;
                node.id = value;
                node.value = objTerminais[value].nome;

                node.className = "cbTerminais";
                
                //==>debug
                //console.log('fillGroup1(cbTerminais) value:', value, 
                //    'Number(value):', objAtendimento.lstTerminais.includes(Number(value)),
                //    'String(value):', objAtendimento.lstTerminais.includes(String(value)),
                //    );

                if (objAtendimento.lstTerminais.includes(Number(value)) ||
                    objAtendimento.lstTerminais.includes(String(value)) )
                    node.checked = true;
                else
                    node.checked = false;

                divNode.appendChild(node);
                divNode.append(objTerminais[value].nome);
            });

            divGroup1.appendChild(divNode);
        }
    } catch (error) {
        writeToConsole(`exception ${error} in fillGroup1`);
        console.log(`exception ${error} in fillGroup1`, error);
    }
}

// Create the DCP table
function fillDcpTable() {

    //==>debug
    //console.log('fillDcpTable() objAtendimento:', objAtendimento);

    writeToConsole();

    try {
        let divDcp = document.getElementById("dcpTable");
        divDcp.innerHTML = "";

        const tbNode = document.createElement("TABLE");
        tbNode.id = "dcpTable";
        tbNode.border = "1";

        let trNode = document.createElement("TR");
        let thNode = document.createElement("TH");
        thNode.innerText = "Porão";
        trNode.appendChild(thNode);

        objAtendimento.lstPoroes.forEach(poraoId => {
            thNode = document.createElement("TH");
            thNode.innerText = poraoId;
            trNode.appendChild(thNode);
        });

        thNode = document.createElement("TH");
        thNode.innerText = "Total";
        trNode.appendChild(thNode);

        tbNode.appendChild(trNode);

        trNode = document.createElement("TR");
        thNode = document.createElement("TH");
        thNode.innerText = "Produto";
        trNode.appendChild(thNode);

        objAtendimento.lstPoroes.forEach(poraoId => {
            let tdNode = document.createElement("TD");
            tdNode.align = "center";

            if (readOnly == false) {
                let selNode = document.createElement("SELECT");
                selNode.name = `poraoSelect_${poraoId}`;
                selNode.id = `${poraoId}`;

                let optNode = document.createElement("OPTION");
                optNode.value = "---";
                optNode.name = `porProd_${poraoId}_0`;
                optNode.innerText = "---";
                selNode.appendChild(optNode);

                //console.log(`objProdutos:`, objProdutos);

                if (objAtendimento.lstProdutos && objAtendimento.lstProdutos.length > 0) {
                    objAtendimento.lstProdutos.forEach(prodId => {

                        if (prodId) {
                            optNode = document.createElement("OPTION");
                            optNode.value = objProdutos[prodId].nome;
                            optNode.name = `porProd_${poraoId}_${prodId}`;
                            optNode.id = `${prodId}`;

                            if (objAtendimento.poroes[poraoId]) {
                                if (objAtendimento.lstProdutos.length == 1 ||
                                    objAtendimento.poroes[poraoId].produto_id == prodId)
                                    optNode.selected = true;

                                optNode.innerText = objProdutos[prodId].nome;
                            }

                            selNode.appendChild(optNode);
                        }
                    });
                }

                tdNode.appendChild(selNode);
            } else {
                let selNode = document.createElement("LABEL");

                if (objAtendimento.lstProdutos && objAtendimento.lstProdutos.length > 0) {
                    objAtendimento.lstProdutos.forEach(prodId => {
                        if (prodId) {
                            if (objAtendimento.poroes[poraoId]) {
                                if (objAtendimento.lstProdutos.length == 1 ||
                                    objAtendimento.poroes[poraoId].produto_id == prodId) {
                                    selNode.innerText = objProdutos[prodId].nome;
                                }
                            }
                        }
                    });
                }

                tdNode.appendChild(selNode);
            }

            trNode.appendChild(tdNode);
        });

        thNode = document.createElement("TH");
        trNode.appendChild(thNode);

        tbNode.appendChild(trNode);

        let poraoTotal = {};

        if (objAtendimento.lstTerminais && objAtendimento.lstTerminais.length > 0) {
            objAtendimento.lstTerminais.forEach(termId => {
                if (termId && termId in objTerminais) {
                    trNode = document.createElement("TR");

                    let tdNode = document.createElement("TD");
                    tdNode.id = `term_${termId}`;

                    if (objAtendimento.terminais.includes(termId)) {
                        tdNode.innerText = `${objTerminais[termId].nome}:${objTerminais[termId].descricao}`;
                    }

                    trNode.appendChild(tdNode);

                    let termTotal = +0.0;

                    objAtendimento.lstPoroes.forEach(poraoId => {
                        tdNode = document.createElement("TD");

                        let node = document.createElement("INPUT");
                        node.type = "text";
                        node.name = `qtdPT_${poraoId}_${termId}`;
                        node.size = "13";
                        node.readOnly = readOnly;

                        let value = +0.0;

                        if (objAtendimento.poroes[poraoId].terminais &&
                            termId in objAtendimento.poroes[poraoId].terminais) {

                            if (objTerminais[termId].descricao.length > 3 &&
                                objTerminais[termId].descricao.substring(0, 3) == "(-)" &&
                                parseFloat(objAtendimento.poroes[poraoId].terminais[termId].quantidade) > 0)
                                objAtendimento.poroes[poraoId].terminais[termId].quantidade *= -1;

                            value = parseFloat(objAtendimento.poroes[poraoId].terminais[termId].quantidade);

                            termTotal += value;

                            if (!poraoTotal[poraoId])
                                poraoTotal[poraoId] = +0.0;

                            poraoTotal[poraoId] += value;

                            //console.log('value', value, 'termTotal', termTotal, `poraoTotal[${poraoId}]`, poraoTotal[poraoId] );
                        }

                        if (value != +0.0)
                            node.value = numeral(value).format('0,0.000');

                        // objAtendimento.poroes[poraoId].terminais[termId].quantidade
                        tdNode.appendChild(node);
                        trNode.appendChild(tdNode);
                    });

                    thNode = document.createElement("TH");
                    thNode.id = `totalTerminal${termId}`;
                    thNode.innerText = numeral(termTotal).format('0,0.000');;

                    trNode.appendChild(thNode);

                    tbNode.appendChild(trNode);
                } else {
                    writeToConsole(`Invalid termId: ${termId}`);
                }
            });
        }

        // Totais
        trNode = document.createElement("TR");
        thNode = document.createElement("TH");
        thNode.innerText = "Totais";
        trNode.appendChild(thNode);

        let totalTotal = +0.000;

        objAtendimento.lstPoroes.forEach(poraoId => {
            let node = document.createElement("TH");
            node.name = `TotalPorao${poraoId}`;

            let value = +0.00;

            if (poraoTotal[poraoId]) {
                value = poraoTotal[poraoId];
                totalTotal += value;
            }

            if (value != +0.00)
                node.innerText = numeral(value).format('0,0.000');

            trNode.appendChild(node);
        });

        thNode = document.createElement("TH");
        thNode.id = `totalTotal`;

        if (totalTotal > 0.00) {
            thNode.innerText = numeral(totalTotal).format('0,0.000');

            objAtendimento.balanca = totalTotal.toFixed(3);
            onUpdateBalancaArqueacao();
        }

        trNode.appendChild(thNode);
        tbNode.appendChild(trNode);

        // Cubagem
        trNode = document.createElement("TR");
        thNode = document.createElement("TH");
        thNode.innerText = "CUBAGEM";
        trNode.appendChild(thNode);

        let cubagemTotal = +0;

        objAtendimento.lstPoroes.forEach(poraoId => {
            let tdNode = document.createElement("TD");

            let node = document.createElement("INPUT");
            node.type = "text";
            node.name = `CubPorao_${poraoId}`;
            node.size = "13";
            node.readOnly = readOnly;

            let value = +0;

            if (objAtendimento.poroes[poraoId].cubagem) {
                value = parseInt(objAtendimento.poroes[poraoId].cubagem);
                cubagemTotal += value;
            }

            if (value != +0)
                node.value = numeral(value).format('0,0');

            tdNode.appendChild(node);
            trNode.appendChild(tdNode);
        });

        thNode = document.createElement("TH");

        trNode.appendChild(thNode);
        tbNode.appendChild(trNode);

        // Fator de estiva
        trNode = document.createElement("TR");
        thNode = document.createElement("TH");
        thNode.innerText = "Fator de estiva";
        trNode.appendChild(thNode);

        //let fatorEstivaTotal = +0.00;

        objAtendimento.lstPoroes.forEach(poraoId => {
            thNode = document.createElement("TH");

            let value = 0.00;

            if (objAtendimento.poroes[poraoId].cubagem) {
                value = parseInt(objAtendimento.poroes[poraoId].cubagem);
                cubagemTotal += value;
            }

            objAtendimento.poroes[poraoId].fatorestiva = (value / poraoTotal[poraoId]);

            thNode.innerText = numeral(parseFloat(objAtendimento.poroes[poraoId].fatorestiva)).format('0.00');

            trNode.appendChild(thNode);

        });

        thNode = document.createElement("TH");

        trNode.appendChild(thNode);
        tbNode.appendChild(trNode);

        // Condicao porao
        trNode = document.createElement("TR");
        thNode = document.createElement("TH");
        thNode.innerText = "Condição do Porão";

        trNode.appendChild(thNode);

        objAtendimento.lstPoroes.forEach(poraoId => {
            let tdNode = document.createElement("TD");
            tdNode.align = "center";

            if (readOnly == false) {

                let selNode = document.createElement("SELECT");
                selNode.name = `cond_${poraoId}`;

                let optNode = document.createElement("OPTION");
                optNode.value = "---";
                optNode.innerText = "---";
                selNode.appendChild(optNode);

                //<option value="FULL"></option>
                //<option value="SLACK"></option>
                optNode = document.createElement("OPTION");
                optNode.value = "FULL";
                optNode.innerText = optNode.value;

                if (objAtendimento.poroes[poraoId].condicao == optNode.value)
                    optNode.selected = true;

                selNode.appendChild(optNode);

                optNode = document.createElement("OPTION");
                optNode.value = "SLACK";
                optNode.innerText = optNode.value;

                if (objAtendimento.poroes[poraoId] && objAtendimento.poroes[poraoId].condicao == optNode.value)
                    optNode.selected = true;

                selNode.appendChild(optNode);
                tdNode.appendChild(selNode);
            } else {
                let selNode = document.createElement("LABEL");
                selNode.innerText = objAtendimento.poroes[poraoId].condicao;
                tdNode.appendChild(selNode);
            }

            trNode.appendChild(tdNode);
        });

        thNode = document.createElement("TH");
        trNode.appendChild(thNode);

        tbNode.appendChild(trNode);
        divDcp.appendChild(tbNode);
    } catch (error) {
        writeToConsole(`exception ${error} in fillDcpTable`);
        console.log(`exception ${error} in fillDcpTable`, error);
    }
}

function disableButton(status = true) {
    let bt = document.getElementById("salvar");
    bt.disabled = status;
}

// Convert excel to json
function convExcel2Json(objExcel) {
    const objDcp = {
        "atdId": 0,
        "codAtendimento": "",
        "data": "",
        "navio": "",
        "balanca": "0.000",
        "arqueacao": "0.000",
        "comando_navio": "0.000",
        "perito_receita": "0.000",
        "outras_partes1_id": "---",
        "outras_partes1": "0.000",
        "outras_partes2_id": "---",
        "outras_partes2": "0.000",
        "outras_partes3_id": "---",
        "outras_partes3": "0.000",
        "excesso": "0.000",
        "falta": "0.000",
        "diferenca": "0.00",
        "lstProdutos": [],
        "lstTerminais": [],
        "lstPoroes": [],
        "produtos": [],
        "terminais": [],
        "poroes": {}
    };

    const navio = 0x0100;
    const dataembarque = 0x0200;
    const pesoterra = 0x0400;
    const pesobordo = 0x0800;
    const variacao = 0x1000;
    const codatendimento = 0x2000;

    const porao = 0x01;
    const produto = 0x02;
    const total = 0x04;
    const cubagem = 0x08;
    const fatorestiva = 0x10;
    const condicao = 0x20;

    let allLines = (porao | produto | total | cubagem | fatorestiva | condicao);

    let lineType = 0;
    let found = false;
    let errorMessage = "";

    let dctPores = {};

    try {
        Object.keys(objExcel).forEach((item) => {
            let sItem = objExcel[item];

            //==>debug
            //console.log("objExcel: sItem:", sItem);

            if (sItem.length == 1 && lineType == 0) {
                objDcp["codAtendimento"] = sItem[0].value;
            }

            if (sItem.length > 1 && sItem[0].type) {

                let tag = sItem[0].tag;

                //==>debug
                //console.log("tag:", tag);

                if ((lineType & allLines) != allLines) {

                    found = false;
                    if (!found && (lineType & navio) == 0 && tag == "navio") {
                        objDcp["navio"] = sItem[1].value;
                        lineType |= navio;
                        found = true;
                    }

                    if (!found && (lineType & dataembarque) == 0 && tag == "dataembarque") {
                        let dataDate = parseDate(sItem[1].value);

                        //==>debug
                        //console.log( "dataDate:", dataDate, ", sItem[1].value:", sItem[1].value );

                        if (dataDate) {
                            objDcp["data"] = dataDate.toISOString().substring(0, 10);
                        }

                        lineType |= dataembarque;
                        found = true;
                    }

                    if (!found && (lineType & codatendimento) == 0 && tag == "codatendimento") {
                        objDcp["codAtendimento"] = sItem[1].value;
                        lineType |= codatendimento;
                        found = true;
                    }

                    if (!found && (lineType & pesoterra) == 0 && tag == "pesoterra") {
                        objDcp["balanca"] = sItem[1].value.toFixed(3);
                        lineType |= pesoterra;
                        found = true;
                    }

                    if (!found && (lineType & pesobordo) == 0 && tag == "pesobordo") {
                        objDcp["arqueacao"] = sItem[1].value.toFixed(3);
                        lineType |= pesobordo;
                        found = true;
                    }

                    if (!found && (lineType & variacao) == 0 && tag == "variacao") {
                        objDcp["diferenca"] = sItem[1].value.toFixed(3);
                        lineType |= variacao;
                        found = true;
                    }

                    if (!found && (lineType & porao) == 0 && tag == "porao") {
                        //colIni = sItem[1].c;

                        //==>debug
                        //console.log("porao sItem:", sItem, "colIni:", colIni);

                        for (let i = 1; i < sItem.length; i++) {

                            let value = parseInt(sItem[i].value);

                            //==>debug
                            //console.log(`porao sItem[${i}]}:`, sItem[i], "value:", value, "(value > 0) && (value < 10):", (value > 0) && (value < 10));
                            
                            if ((value > 0) && (value < 10)) {

                                //==>debug
                                //console.log(`!('${sItem[i].c}' in dctPores)`, !(`'${sItem[i].c}'` in dctPores));

                                if (!(`'${sItem[i].c}'` in dctPores))
                                    dctPores[`'${sItem[i].c}'`] = `${value}`;

                                objDcp["poroes"][`${value}`] = {
                                    "produto_id": 0,
                                    "terminais": {},
                                    "cubagem": 0,
                                    "fatorestiva": 0.00,
                                    "condicao": "---"
                                };
                                objDcp.lstPoroes.push(`${value}`);                            
                            }
                        }

                        //==>debug
                        //console.log('porao (2) objDcp["poroes"]:', objDcp["poroes"]);
                        //console.log("porao (2) objDcp.lstPoroes:", objDcp.lstPoroes);
                        //console.log("porao (2) dctPores:", dctPores);

                        lineType |= porao;
                        found = true;
                    }

                    if (!found && (lineType & produto) == 0 && tag == "produto") {

                        //==>debug
                        //let colIni = sItem[0].c + 1;

                        for (let i = 1; i < sItem.length; i++) {
                            let prodId = (sItem[i].tag in objProdutosTags) ? Number(objProdutosTags[sItem[i].tag]) : 0;

                            //==>debug
                            //console.log(`sItem[${i}].tag:`, sItem[i].tag, "prodId:", prodId);

                            if (prodId > 0 && prodId < 100) {
                                //objDcp["produtos"][prodId] = {...objProdutos[prodId] };
                                if (!objDcp["produtos"].includes(prodId))
                                    objDcp["produtos"].push(prodId);

                                if (`'${sItem[i].c}'` in dctPores) {
                                    let poraoId = dctPores[`'${sItem[i].c}'`];

                                    objDcp["poroes"][poraoId]["produto_id"] = prodId;

                                    if (!objDcp.lstProdutos.includes(prodId))
                                        objDcp.lstProdutos.push(prodId);
                                }
                                
                            } else {
                                if (sItem[i].tag.length > 0 && sItem[i].tag != "empty")
                                    errorMessage += `[Produto desconhecido: ${sItem[i].tag}]`;
                            }
                        }

                        lineType |= produto;
                        found = true;
                    }

                    if (!found && (lineType & total) == 0 && tag == "total") {
                        //console.log(tag, sItem);																
                        lineType |= total;
                        found = true;
                    }

                    if (!found && (lineType & cubagem) == 0 && tag == "cubagem") {
                        //console.log(tag, sItem);

                        for (let i = 1; i < sItem.length; i++) {
                            if (parseInt(sItem[i].value) != 0 && (`'${sItem[i].c}'` in dctPores)) {

                                let poraoId = dctPores[`'${sItem[i].c}'`];
                                if (poraoId in objDcp.poroes)
                                    objDcp.poroes[poraoId]["cubagem"] = parseInt(sItem[i].value);
                            }
                        }

                        lineType |= cubagem;
                        found = true;
                    }

                    if (!found && (lineType & fatorestiva) == 0 && tag.substring(0,5) == "fator") {
                        //==>debug
                        //console.log(tag, sItem);

                        for (let i = 1; i < sItem.length; i++) {
                            if (parseFloat(sItem[i].value) > 0 && (`'${sItem[i].c}'` in dctPores)) {
                                let poraoId = dctPores[`'${sItem[i].c}'`];
                                if (poraoId in objDcp.poroes)
                                    objDcp.poroes[poraoId]["fatorestiva"] = parseFloat(sItem[i].value).toFixed(2);
                            }
                        }

                        lineType |= fatorestiva;
                        found = true;
                    }

                    if (!found && (lineType & condicao) == 0 && tag.substring(0,8) == "condicao") {

                        //==>debug
                        //console.log(tag, sItem);

                        for (let i = 1; i < sItem.length; i++) {

                            //console.log( "sItem[i]", sItem[i]);

                            if (sItem[i].value.length > 0 && (`'${sItem[i].c}'` in dctPores)) {
                                let poraoId = dctPores[`'${sItem[i].c}'`];
                                if (poraoId in objDcp.poroes)
                                    objDcp.poroes[poraoId]["condicao"] = sItem[i].value.trim().toUpperCase();

                                //&& (sItem[i].value.toUpperCase() == "FULL" || sItem[i].value.toUpperCase() == "SLACK" ))
                            }
                        }

                        lineType |= condicao;
                        found = true;
                    }

                    if (!found && ((lineType & allLines) != 0)) {
                        //==>debug
                        //console.log(tag, sItem);

                        let termId = Number(objTerminaisTags[tag]);
                        //let colIni = sItem[0].c + 1;

                        if (termId != undefined) {
                            for (let i = 1; i < sItem.length - 1; i++) {
                                if (`'${sItem[i].c}'` in dctPores) {
                                    let value = parseFloat(sItem[i].value);
                                    let poraoId = dctPores[`'${sItem[i].c}'`];

                                    if (value > 0 && (poraoId in objDcp.poroes)) {
                                        if (!objDcp.lstTerminais.includes(termId))
                                            objDcp.lstTerminais.push(termId);

                                        if (objDcp.poroes[poraoId]["terminais"] == undefined)
                                            objDcp.poroes[poraoId]["terminais"] = {};

                                        if (!objDcp.terminais.includes(termId))
                                            objDcp.terminais.push(termId);
                                    }

                                    objDcp.poroes[poraoId]["terminais"][termId] = { "quantidade": value.toFixed(3) };
                                }
                            }

                            found = true;
                        } else {
                            errorMessage += `[Terminal desconhecido: ${tag}]`;
                        }
                    }

                }
            }
        });

    } catch (error) {
        let errorMsg = `[exception ${error} in convExcel2Json. lineType: ${lineType.toString(2)}]`;
        errorMessage += errorMsg;

        console.log(errorMsg, error);
    }

    if ((lineType & allLines) != allLines) {

        let missing = "";

        if ((lineType & porao) != porao) missing += "porao";

        if ((lineType & produto) != produto) missing += missing.length == 0 ? "produto" : ",produto";

        if ((lineType & total) != total) missing += missing.length == 0 ? "total" : ",total";

        if ((lineType & cubagem) != cubagem) missing += missing.length == 0 ? "cubagem" : ",cubagem";

        if ((lineType & fatorestiva) != fatorestiva) missing += missing.length == 0 ? "fatorestiva" : ",fatorestiva";

        if ((lineType & condicao) != condicao) missing += missing.length == 0 ? "condicao" : ",condicao";

        errorMessage += `[convExcel2Json. req fields missing: ${missing}]`;
    }

    //if (objDcp.lstPoroes && objDcp.lstPoroes.length > 0)
    //    objDcp.lstPoroes.sort();
    // //if (objDcp.lstProdutos && objDcp.lstProdutos.length > 0)
    //    objDcp.lstProdutos.sort();
    // //if (objDcp.produtos && objDcp.produtos.length > 0)
    //    objDcp.produtos.sort();
    // //if (objDcp.lstTerminais && objDcp.lstTerminais.length > 0)
    //    objDcp.lstTerminais.sort();
    // //if (objDcp.terminais && objDcp.terminais.length > 0)
    //    objDcp.terminais.sort();

    //console.log('objDcp(convExcel2Json):', objDcp);

    if (errorMessage.length > 0)
        setTimeout(writeToConsole, 1000, errorMessage);

    return objDcp;
}

async function sendJson(json, apiURL) {
    let id = (json && json.atdId) ? json.atdId : 0;
    let action = (id == 0) ? "POST" : "PUT";

    let headers = new Headers();
    //let formData = new FormData();

    headers.append('Accept', 'application/json');
    headers.append("Content-type", "application/json");

    return await fetch(apiURL, {
            headers: headers,
            mode: 'cors', // no-cors, *cors, same-origin
            body: JSON.stringify(json),
            method: action, // *GET, POST, PUT, DELETE, etc.
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        })
        .then(response => {
            return response.text();
        })
        .then(text => {
            
            //console.log("response:", text);

            if (text.length > 0)
                return JSON.parse(text);
            else
                return {
                    "error": "empty return"
                };
        })
        .catch(err => {
            let error = `catch: fetch sendJson: ${JSON.stringify(err)}`;
            writeToConsole(error);
            return {
                "error": error
            };
        })
}

async function sendFiles(files, apiURL) {
    //==>debug
    console.log("sendFiles files:", files, " apiURL:", apiURL );

    let ctdFiles = files.length;

    if (ctdFiles > 0) {
        let headers = new Headers();
        let formData = new FormData();

        // Read selected files
        for (var index = 0; index < ctdFiles; index++) {
            //==>debug
            console.log("index:", index, files[index]);

            formData.append("files[]", files[index]);
        }

        //==>debug
        writeToConsole(`formData:${JSON.stringify(formData.getAll("files[]"))}`);

        headers.append('Accept', 'application/json');
        //headers.append("Content-type", "multipart/form-data");

        return await fetch(apiURL, {
                headers: headers,
                mode: 'cors', // no-cors, *cors, same-origin
                body: formData,
                method: "POST", // *GET, POST, PUT, DELETE, etc.
                cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                credentials: 'same-origin', // include, *same-origin, omit
                redirect: 'follow', // manual, *follow, error
                referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            })
            .then(response => {
                //==>debug
                console.log("sendFiles response:", response);

                return response.json();
            })
            .then(json => {
                //==>debug                
                console.log("sendFiles json:", json);
                
                return json;
            })
            .catch(err => {
                let error = `catch: fetch sendFiles: ${JSON.stringify(err)}`;
                writeToConsole(error);
            })
    }

}

async function onSaveForm1(evt) {
    writeToConsole();
    disableButton(true);

    let id = (objAtendimento && objAtendimento.atdId) ? objAtendimento.atdId : 0;
    let f = document.getElementById("planodecarga");
    let files = f.files;

    //==>debug
    console.log("onSaveForm1: files:", f, files);

    var hasChanges = 0;

    if (files.length > 0)
        hasChanges |= 4;

    //==>debug
    //console.log("onSaveForm1: objAtendimento:", objAtendimento);
    //console.log("onSaveForm1: savedObjAtendimento:", savedObjAtendimento);

    if (Number(id) > 0) { // Update

        objAtendimento.hasChanged = cmpAtendimentoDcp(objAtendimento, savedObjAtendimento);

        if (objAtendimento.hasChanged)
            hasChanges |= 1;

        if ("dcpFileToDelete" in objAtendimento && objAtendimento.dcpFileToDelete.length > 0)
            hasChanges |= 2;

        //==>debug
        //console.log(`hasChanged:${JSON.stringify(objAtendimento.hasChanged)}`);
        
        if (hasChanges) {

            if ((hasChanges & 3) != 0) {
                await sendJson(objAtendimento, `/atendimentos/api/atendimentos.php`)
                    .then(json => {
                        if (json["error"]) {
                            writeToConsole(`json["updated"]["err_code"]: ${JSON.stringify(json["updated"]["err_code"])}`);
                            console.log('json["updated"]["err_code"]:', json["error"]);
                        } else {

                            //==>debug
                            //console.log('json["updated"]:', json["updated"]);

                            //console.log( 'sendJson:', json);
                            //console.log( 'atdId:', json.atdId );

                            if (atdId != json.atdId) {
                                //==>debug
                                //console.log(`onSaveForm1: atdId(${atdId}) != json.atdId(${json.atdId})`);

                                atdId = json.atdId;
                                objAtendimento.atdId = atdId;
                            }

                            savedObjAtendimento = JSON.parse(JSON.stringify(objAtendimento));
                        }
                    })
                    .catch(err => {
                        let error = `catch: sendJson: ${JSON.stringify(err)}`;
                        writeToConsole(error);
                        console.log(error, err);
                    });
            }

            if ((hasChanges & 4) != 0 && Number(atdId) > 0) {
                await sendFiles(files, `/atendimentos/api/planos.php?id=${atdId}`)
                    //sendFiles(files, `${baseUrl}/api/process.php?id=${atdId}`)
                    .then((json) => {
                        //==>debug
                        console.log(`sendFiles:${JSON.stringify(json)}`, json);
                    })
                    .catch(err => {
                        let error = `catch: sendFiles: ${JSON.stringify(err)}`;
                        writeToConsole(error);
                        console.log("err:", err);
                    });
            }

            if (Number(atdId) > 0) {
                let json = await loadAtendimento(atdId)
                    .catch((err) => {
                        let error = `loadAtendimento promisse exception: ${JSON.stringify(err)}`;
                        console.log(error, err);
                        return;
                    });
            }

            writeToConsole(`Updated (${atdId})!`);

        } else {
            writeToConsole("Nothing to save!");
        }

    } else { // New atendimento

        await sendJson(objAtendimento, `/atendimentos/api/atendimentos.php`)
            .then(async(json) => {
                if (json["error"]) {
                    writeToConsole(`json["error"]: ${JSON.stringify(json["error"])}`);
                    console.log('json["error"]:', json["err_code"], json["error"]);
                } else {

                    //==>debug
                    //console.log('json:', json);
                    //console.log('atdId:', json.atdId);

                    if (atdId != json.atdId) {
                        //==>debug
                        //console.log(`onSaveForm1 (new atendimento): atdId(${atdId}) != json.atdId(${json.atdId})`);
                        
                        atdId = Number(json.atdId);
                        objAtendimento.atdId = atdId;
                    }

                    if (Number(atdId) > 0) {

                        if (files.length > 0) {
                            await sendFiles(files, `/atendimentos/api/planos.php?id=${atdId}`)
                                //sendFiles(files, `${baseUrl}/api/process.php?id=${atdId}`)
                                .then((json) => {
                                    //==>debug
                                    console.log(`sendFiles:${JSON.stringify(json)}`, json);
                                })
                                .catch(err => {
                                    let error = `catch: sendFiles: ${JSON.stringify(err)}`;
                                    writeToConsole(error);
                                    console.log("err:", err);
                                });
                        }

                        objAtendimento = await loadAtendimento(atdId)
                            .catch((err) => {
                                let error = `loadAtendimento promisse exception: ${JSON.stringify(err)}`;
                                console.log(error, err);
                                return;
                            });

                        //==>debug
                        //console.log('onSaveForm1: objAtendimento:', objAtendimento, 'savedObjAtendimento:', savedObjAtendimento);
                    }

                    writeToConsole(`Saved New atendimento (${atdId})!`);
                }
            })
            .catch(err => {
                let error = `catch: sendJson: ${JSON.stringify(err)}`;
                writeToConsole(error);
                console.log(error, err);
            });

    }

}

function onDeleteFile(evt) {
    //==>debug
    //console.log("onDeleteFile evt.target:", evt.target)
    writeToConsole();

    if ("dcpFileToDelete" in objAtendimento && objAtendimento.dcpFileToDelete.length > 0) {
        //==>debug
        //console.log("onDeleteFile ctdFiles:", objAtendimento.dcpFileToDelete.length);

        let headers = new Headers();
        headers.append('Accept', 'application/json');
        headers.append("Content-type", "application/json");

        fetch(`/atendimentos/api/planos.php?id=${atdId}`, {
                headers: headers,
                mode: 'cors', // no-cors, *cors, same-origin
                body: JSON.stringify(objAtendimento.dcpFileToDelete),
                method: 'DELETE', // *GET, POST, PUT, DELETE, etc.
                cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                credentials: 'same-origin', // include, *same-origin, omit
                redirect: 'follow', // manual, *follow, error
                referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            })
            .then(response => {
                return response.text();
            })
            .then(async(text) => {
                //==>debug
                //console.log("response onDeleteFile:", text);

                fetch(`/atendimentos/api/planos.php?id=${atdId}&mode=list`, {
                        mode: 'cors', // no-cors, *cors, same-origin
                        method: "GET", // *GET, POST, PUT, DELETE, etc.
                        headers: headers,
                        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                        credentials: 'same-origin', // include, *same-origin, omit
                        redirect: 'follow', // manual, *follow, error
                        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
                    })
                    .then((response) => {
                        return response.json();
                    })
                    .then(json => {
                        //==>debug
                        //console.log("response onDeleteFile(get planos):", json);

                        objAtendimento.lstPlanos = JSON.parse(JSON.stringify(json));

                        fillDeleteFileList();

                    });

            })
            .catch(err => {
                let error = `catch: fetch onDeleteFile: ${JSON.stringify(err)}`;
                writeToConsole(error);
                return {
                    "error onDeleteFile": error
                };
                console.log("err exception onDeleteFile:", err);
            })

    }
}

// update the objAtendimento filds with excel values
function setAtendimentoDcp(objAtd1, objAtd2) {
    let errorMsg = "";

    //==>debug
    console.log("start(setAtendimentoDcp): objAtd1", objAtd1, "objAtd2", objAtd2);

    if (objAtd2.codAtendimento && (objAtd1.codAtendimento != objAtd2.codAtendimento)) {
        
        //==>debug
        //console.log( 'objAtd2.codAtendimento && (objAtd1.codAtendimento != objAtd2.codAtendimento): ', 
        //    objAtd1.codAtendimento, objAtd2.codAtendimento );

        errorMsg += `[codAtendimento(${objAtd2.codAtendimento}) != old(${objAtd1.codAtendimento})]`;
        objAtd1.codAtendimento = objAtd2.codAtendimento;
    }

    if (objAtd2.data && (objAtd1.data != objAtd2.data)) {

        //==>debug
        //console.log( 'objAtd2.data && (objAtd1.data != objAtd2.data): ', 
        //    objAtd1.data, objAtd2.data );

        errorMsg += `[data(${objAtd2.data}) != old(${objAtd1.data})]`;
        objAtd1.data = objAtd2.data;
    }

    if (objAtd2.navio && (objAtd1.navio != objAtd2.navio) ) {

        //==>debug
        //console.log( 'objAtd2.navio && (objAtd1.navio != objAtd2.navio): ', 
        //    objAtd1.navio, objAtd2.navio );
    
        errorMsg += `[navio(${objAtd2.navio}) != old(${objAtd1.navio})]`;
        objAtd1.navio = objAtd2.navio;
    }

    if (Number(objAtd2.balanca) > 0) {
        objAtd1.balanca = objAtd2.balanca;
    }

    if (Number(objAtd2.arqueacao) > 0) {
        objAtd1.arqueacao = objAtd2.arqueacao;
    }

    if (Number(objAtd2.comando_navio) > 0) {
        objAtd1.comando_navio = objAtd2.comando_navio;
    }

    if (Number(objAtd2.perito_receita) > 0) {
        objAtd1.perito_receita = objAtd2.perito_receita;
    }

    if (Number(objAtd2.outras_partes1) > 0) {
        objAtd1.outras_partes1 = objAtd2.outras_partes1;
        objAtd1.outras_partes1_id = objAtd2.outras_partes1_id;
    }

    if (Number(objAtd2.outras_partes2) > 0) {
        objAtd1.outras_partes2 = objAtd2.outras_partes2;
        objAtd1.outras_partes2_id = objAtd2.outras_partes2_id;
    }

    if (Number(objAtd2.outras_partes3) > 0) {
        objAtd1.outras_partes3 = objAtd2.outras_partes3;
        objAtd1.outras_partes3_id = objAtd2.outras_partes3_id;
    }

    objAtd1.excesso = objAtd2.excesso;
    objAtd1.falta = objAtd2.falta;
    objAtd1.diferenca = objAtd2.diferenca;

    if (objAtd1.poroes != objAtd2.poroes) {
        objAtd1.poroes = {};

        Object.keys(objAtd2.poroes).forEach(poraoId => {
            objAtd1.poroes[poraoId] = JSON.parse(JSON.stringify(objAtd2.poroes[poraoId]));
            //{...objAtd2.poroes[poraoId]};
        });
    }

    if (objAtd1.lstPoroes != objAtd2.lstPoroes) {
        objAtd1.lstPoroes = JSON.parse(JSON.stringify(objAtd2.lstPoroes));
        //[...objAtd2.lstPoroes];
    }

    if (objAtd1.lstProdutos != objAtd2.lstProdutos) {
        objAtd1.lstProdutos = JSON.parse(JSON.stringify(objAtd2.lstProdutos));
        //[...objAtd2.lstProdutos];
    }

    if (objAtd1.produtos != objAtd2.produtos) {
        objAtd1.produtos = JSON.parse(JSON.stringify(objAtd2.produtos));
        //[...objAtd2.produtos];
    }

    if (objAtd1.lstTerminais != objAtd2.lstTerminais) {
        objAtd1.lstTerminais = JSON.parse(JSON.stringify(objAtd2.lstTerminais));
        //[...objAtd2.lstTerminais];
    }

    if (objAtd1.terminais != objAtd2.terminais) {
        objAtd1.terminais = JSON.parse(JSON.stringify(objAtd2.terminais));
        //[...objAtd2.terminais];
    }

    if (errorMsg.length > 0) {
        //==>debug
        //console.log("end(setAtendimentoDcp): objAtd1", objAtd1);
        
        setTimeout(writeToConsole, 2000, errorMsg);
        console.log("errors:", errorMsg);
    }


}

function cmpAtendimentoDcp(objAtd1, objAtd2) {
    //==>debug
    console.log("cmpAtendimentoDcp: objAtd1", objAtd1, "objAtd2", objAtd2);

    let retCmp = 0;

    if (!objAtd1 || !objAtd1.atdId ||
        objAtd1.codAtendimento != objAtd2.codAtendimento ||
        objAtd1.data != objAtd2.data ||
        objAtd1.navio != objAtd2.navio ||
        parseFloat(objAtd1.balanca) != parseFloat(objAtd2.balanca) ||
        parseFloat(objAtd1.arqueacao) != parseFloat(objAtd2.arqueacao) ||
        parseFloat(objAtd1.comando_navio) != parseFloat(objAtd2.comando_navio) ||
        parseFloat(objAtd1.perito_receita) != parseFloat(objAtd2.perito_receita) ||
        parseFloat(objAtd1.outras_partes1) != parseFloat(objAtd2.outras_partes1) ||
        objAtd1.outras_partes1_id != objAtd2.outras_partes1_id ||
        parseFloat(objAtd1.outras_partes2) != parseFloat(objAtd2.outras_partes2) ||
        objAtd1.outras_partes2_id != objAtd2.outras_partes2_id ||
        parseFloat(objAtd1.outras_partes3) != parseFloat(objAtd2.outras_partes3) ||
        objAtd1.outras_partes3_id != objAtd2.outras_partes3_id ||
        parseFloat(objAtd1.excesso) != parseFloat(objAtd2.excesso) ||
        parseFloat(objAtd1.falta) != parseFloat(objAtd2.falta) ||
        parseFloat(objAtd1.diferenca) != parseFloat(objAtd2.diferenca)) {

        retCmp |= 1;

        //==>debug
        //console.log("cmpAtendimentoDcp: part 1")        
        //if (objAtd1.codAtendimento != objAtd2.codAtendimento) console.log(objAtd1.codAtendimento, objAtd2.codAtendimento);
        //if (objAtd1.data != objAtd2.data) console.log(objAtd1.data, objAtd2.data);
        //if (objAtd1.navio != objAtd2.navio) console.log(objAtd1.navio, objAtd2.navio);
        //if (parseFloat(objAtd1.balanca) != parseFloat(objAtd2.balanca)) console.log(parseFloat(objAtd1.balanca), parseFloat(objAtd2.balanca));
        //if (parseFloat(objAtd1.arqueacao) != parseFloat(objAtd2.arqueacao)) console.log(parseFloat(objAtd1.arqueacao), parseFloat(objAtd2.arqueacao));
        //if (parseFloat(objAtd1.comando_navio) != parseFloat(objAtd2.comando_navio)) console.log(parseFloat(objAtd1.comando_navio), parseFloat(objAtd2.comando_navio));
        //if (parseFloat(objAtd1.perito_receita) != parseFloat(objAtd2.perito_receita)) console.log(parseFloat(objAtd1.perito_receita), parseFloat(objAtd2.perito_receita));
        //if (parseFloat(objAtd1.outras_partes1) != parseFloat(objAtd2.outras_partes1)) console.log(parseFloat(objAtd1.outras_partes1), parseFloat(objAtd2.outras_partes1));
        //if (objAtd1.outras_partes1_id != objAtd2.outras_partes1_id) console.log(objAtd1.outras_partes1_id, objAtd2.outras_partes1_id);
        //if (parseFloat(objAtd1.outras_partes2) != parseFloat(objAtd2.outras_partes2)) console.log(parseFloat(objAtd1.outras_partes2), parseFloat(objAtd2.outras_partes2));
        //if (objAtd1.outras_partes2_id != objAtd2.outras_partes2_id) console.log(objAtd1.outras_partes2_id, objAtd2.outras_partes2_id);
        //if (parseFloat(objAtd1.outras_partes3) != parseFloat(objAtd2.outras_partes3)) console.log(parseFloat(objAtd1.outras_partes3), parseFloat(objAtd2.outras_partes3));
        //if (objAtd1.outras_partes3_id != objAtd2.outras_partes3_id) console.log(objAtd1.outras_partes3_id, objAtd2.outras_partes3_id);
        //if (parseFloat(objAtd1.excesso) != parseFloat(objAtd2.excesso)) console.log(parseFloat(objAtd1.excesso), parseFloat(objAtd2.excesso));
        //if (parseFloat(objAtd1.falta) != parseFloat(objAtd2.falta)) console.log(parseFloat(objAtd1.falta), parseFloat(objAtd2.falta));
        //if (parseFloat(objAtd1.diferenca) != parseFloat(objAtd2.diferenca)) console.log(parseFloat(objAtd1.diferenca), parseFloat(objAtd2.diferenca));

    }

    if (
        objAtd1.lstProdutos.length != objAtd2.lstProdutos.length ||
        (objAtd1.lstProdutos.filter((item) => {
            return !objAtd2.lstProdutos.includes(item);
        })).length > 0
    ) {
        //==>debug
        //console.log("cmpAtendimentoDcp: part 2 - lstProdutos")
        retCmp |= 2;
    }

    if (
        objAtd1.lstTerminais.length != objAtd2.lstTerminais.length ||
        (objAtd1.lstTerminais.filter((item) => {
            return !objAtd2.lstTerminais.includes(item);
        })).length > 0
    ) {
        //==>debug
        //console.log("cmpAtendimentoDcp: part 4 - lstTerminais")
        retCmp |= 4;
    }

    if (
        objAtd1.lstPoroes.length != objAtd2.lstPoroes.length ||
        (objAtd1.lstPoroes.filter((item) => {
            return !objAtd2.lstPoroes.includes(item);
        })).length > 0
    ) {
        //==>debug
        //console.log("cmpAtendimentoDcp: part 8 - lstPoroes");
        retCmp |= 8;
    }

    Object.keys(objAtd1.poroes).forEach(poraoId => {
        let filterCount =
            (Object.keys(objAtd1.poroes[poraoId]).filter((item) => {

                let retval = false;

                if (!(poraoId in objAtd2.poroes) ||
                    !(item in objAtd2.poroes[poraoId]))
                    retval = false;
                else {
                    switch (item) {
                        case "fatorestiva":
                        case "cubagem":
                            {
                                retval = (parseFloat(objAtd1.poroes[poraoId][item]) != parseFloat(objAtd2.poroes[poraoId][item]));
                                break;
                            }
                        case "condicao":
                            {
                                retval = (objAtd1.poroes[poraoId][item]).toUpperCase() != (objAtd2.poroes[poraoId][item]).toUpperCase();
                                break;
                            }
                    }
                }

                return retval;

            }));

        //==>debug
        //console.log( "filterCount:", filterCount );

        if (filterCount.length > 0) {
            //==>debug
            //console.log("cmpAtendimentoDcp: part 16 - poroes ", objAtd1, objAtd2)
            retCmp |= 16;
        }

    });

    Object.keys(objAtd1.poroes).forEach(poraoId => {
        if ("terminais" in objAtd1.poroes[poraoId]) {
            Object.keys(objAtd1.poroes[poraoId]["terminais"]).forEach(termId => {

                //console.log("poraoId:", poraoId, "termId:", termId,
                //    'objAtd1.poroes[poraoId]["terminais"][termId]["quantidade"]:', Number(objAtd1.poroes[poraoId]["terminais"][termId]["quantidade"]),
                //    'objAtd2.poroes[poraoId]["terminais"][termId]["quantidade"]:', Number(objAtd2.poroes[poraoId]["terminais"][termId]["quantidade"]));

                if (!(poraoId in objAtd2.poroes) || !("terminais" in objAtd2.poroes[poraoId]) ||
                    !(termId in objAtd2.poroes[poraoId]["terminais"]) ||
                    !("quantidade" in objAtd2.poroes[poraoId]["terminais"][termId]) ||
                    Number(objAtd1.poroes[poraoId]["terminais"][termId]["quantidade"]) !=
                    Number(objAtd2.poroes[poraoId]["terminais"][termId]["quantidade"])) {

                    //==>debug
                    //console.log("cmpAtendimentoDcp: part 32 - poroes->terminais")
                    retCmp |= 32;
                }
            });
        } else {
            //==>debug
            //console.log("objAtd1.poroes[poraoId]:", objAtd1.poroes[poraoId]);
        }
    });

    if (retCmp > 0) {
        disableButton(false);
    }

    return retCmp;
}

function updateJsonAtd(objAtd) {

    objAtd.lstProdutos = [];
    objAtd.lstTerminais = [];
    objAtd.lstPoroes = [];

    //==>debug
    //console.log('objAtd ini:', objAtd );

    if ("produtos" in objAtd) {
        objAtd.lstProdutos = JSON.parse(JSON.stringify(objAtd.produtos));
    }

    if ("terminais" in objAtd)
        objAtd.lstTerminais = JSON.parse(JSON.stringify(objAtd.terminais));

    if ("poroes" in objAtd) {
        Object.keys(objAtd.poroes).forEach(poraoId => {
            objAtd.lstPoroes.push(poraoId);
        });
    } else {
        objAtd.poroes = {};
    }

    //==>debug
    //console.log('objAtd fin:', objAtd );
}

async function loadAtendimento(atdId) {

    return new Promise((resolve, reject) => {
        if (parseInt(atdId) > 0) {
            //==>debug
            //console.log("loadAtendimento fetch call:", `/atendimentos/api/atendimentos.php?id=${atdId}&mode=json`);

            let headers = new Headers();
            headers.append('Accept', 'application/json');

            fetch(`/atendimentos/api/atendimentos.php?id=${atdId}&mode=json`, {
                    mode: 'cors', // no-cors, *cors, same-origin
                    method: "GET", // *GET, POST, PUT, DELETE, etc.
                    headers: headers,
                    cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                    credentials: 'same-origin', // include, *same-origin, omit
                    redirect: 'follow', // manual, *follow, error
                    referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
                })
                .then( async response => {
                    //==>debug
                    //console.log( 'atendimentos->response:', response );
                    if( response.ok ) {
                        return response.text();
                    } else {
                        response["error"] = await response.text();
                        return JSON.stringify(response);
                    }
                })
                .then(res_text => {
            
                    //==>debug
                    //console.log( 'atendimentos->response_text:', res_text );                    
            
                    let json = JSON.parse( res_text );
            
                    if (json.error) {
                        writeToConsole(`loadAtendimento error: ${JSON.stringify(json.error)}`);
                        reject(json.error);
                    }

                    objAtendimento = JSON.parse(JSON.stringify(json[atdId]));
                    objAtendimento["atdId"] = atdId;

                    updateJsonAtd(objAtendimento);

                    savedObjAtendimento = JSON.parse(JSON.stringify(objAtendimento));

                    //==>debug
                    //console.log('loadAtendimento objAtendimento:', objAtendimento);
                    //console.log('loadAtendimento savedObjAtendimento:', savedObjAtendimento);

                    crateMainApp();
                    fillDeleteFileList();
                    fillGroup1();

                    fillDcpTable();

                    resolve(objAtendimento);
                })
                .catch((err) => {
                    let error = `catch: fetch objAtendimento: ${err.message}`;
                    writeToConsole(error);
                    reject(error);
                });
        } else resolve();
    });
}

async function onLoadBody() {
    
    writeToConsole();

    atdId = getParameterByName('atdid');
    readOnly = getParameterByName('edit') ? true : false;

    //==>debug
    //console.log("onLoadBody: atdId:", atdId);

    numeral.locale(szCurrentLocale);

    Promise.all([pProdutos, pTerminais])
        .then(async(values) => {
            //==>debug
            //console.log(`onLoadBody: values.length: ${values.length}`);
            //console.log("onLoadBody: objProdutos/objTerminais:", objProdutos, objTerminais);

            if (objProdutosStatus.substring(0, 5) == 'Error' ||
                objTerminaisStatus.substring(0, 5) == 'Error') {
                writeToConsole(`Error loading files: objProdutosStatus:${objProdutosStatus}, objTerminaisStatus:${objTerminaisStatus}`);
            } else {
                if (!atdId || Number(atdId) == 0) {
                    objAtendimento = {
                        "atdId": 0,
                        "codAtendimento": "",
                        "data": new Date().toISOString().substring(0, 10),
                        "navio": "",
                        "balanca": "0.000",
                        "arqueacao": "0.000",
                        "comando_navio": "0.000",
                        "perito_receita": "0.000",
                        "outras_partes1_id": "---",
                        "outras_partes1": "0.000",
                        "outras_partes2_id": "---",
                        "outras_partes2": "0.000",
                        "outras_partes3_id": "---",
                        "outras_partes3": "0.000",
                        "excesso": "0.000",
                        "falta": "0.000",
                        "diferenca": "0.00",
                        "lstProdutos": [],
                        "lstTerminais": [],
                        "lstPoroes": [],
                        "produtos": [],
                        "terminais": [],
                        "poroes": {}
                    };

                    atdId = 0;

                    updateJsonAtd(objAtendimento);
                    savedObjAtendimento = JSON.parse(JSON.stringify(objAtendimento));

                    //==>debug
                    //console.log( 'objAtendimento:', objAtendimento);
                    //console.log( 'savedObjAtendimento:', savedObjAtendimento);

                    crateMainApp();
                    fillDeleteFileList();
                    fillGroup1();
                    fillDcpTable();

                } else {
                    objAtendimento = await loadAtendimento(atdId);

                    //==>debug
                    console.log('objAtendimento(onLoadBody):', objAtendimento);
                }
            }
        });

}

const pTerminais = fetch(`/atendimentos/api/terminais.php?mode=json`)
    .then( async response => {
        //==>debug
        //console.log( 'pTerminais->response:', response );
        
        if( response.ok ) {
            return response.text();
        } else {
            response["error"] = await response.text();
            return JSON.stringify(response);
        }
    })
    .then(res_text => {

        //==>debug
        //console.log( 'pTerminais->response_text:', res_text );                    

        let json = JSON.parse( res_text );

        objTerminaisTags = {};

        if (json.error) {
            objTerminaisStatus = `Error objTerminais: ${json.error}`;
        } else {
            objTerminaisStatus = `Loaded objTerminais: ${Object.keys(json).length} recs`;
            objTerminais = JSON.parse(JSON.stringify(json));

            //==>debug
            //console.log('objTerminais:', objTerminais);

            Object.keys(objTerminais).forEach(term => {
                let arrTags = objTerminais[term].tags.split(",");
                arrTags.forEach(tag => {
                    if (tag && tag.length > 0)
                        objTerminaisTags[tag] = term;
                });
            });
        }
    })
    .catch((err) => {
        objTerminaisStatus = `Error objTerminais: ${err.message}`;
        console.log(objTerminaisStatus, err);

        let error = `catch: fetch Terminais: ${err.message}`;
        writeToConsole(error);
    });

const pProdutos = fetch(`/atendimentos/api/produtos.php?mode=json`)
    .then( async response => {
        //==>debug
        //console.log( 'pProdutos->response:', response );
        if( response.ok ) {
            return response.text();
        } else {
            response["error"] = await response.text();
            return JSON.stringify(response);
        }
    })
    .then(res_text => {

        //==>debug
        //console.log( 'pProdutos->response_text:', res_text );                    

        let json = JSON.parse( res_text );

        objProdutosTags = {};

        if (json.error) {
            objProdutosStatus = `Error objProdutos: ${json.error}`;
        } else {
            objProdutosStatus = `Loaded objProdutos: ${Object.keys(json).length} recs`;
            objProdutos = JSON.parse(JSON.stringify(json));

            //==>debug
            //console.log('objProdutos:', objProdutos);

            Object.keys(objProdutos).forEach(prod => {
                let arrTags = objProdutos[prod].tags.split(",");
                arrTags.forEach(tag => {
                    if (tag && tag.length > 0)
                        objProdutosTags[tag] = prod;
                });
            });
        }

    })
    .catch((err) => {
        objProdutosStatus = `Error objProdutos: ${err.message}`;
        console.log(objProdutosStatus, err);

        let error = `catch: fetch Produtos: ${err.message}`;
        writeToConsole(error);

    })

onLoadBody();