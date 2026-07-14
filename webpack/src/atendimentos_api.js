import './style.css';

import { numeral,szCurrentLocale,parseDate,getParameterByName,writeToConsole, 
    trace_log,debug_log,loadTerminais,loadProdutos,deepCopyObject,_copyRecursive, crc32} 
    from '../lib/helper_functions.js';  

import {qrcode} from '../lib/qrcode.js';

// chatGPT: generate a regular expression to find lines with the command "console.log" that don't comented by two slash characters in javascript
// ^(?!.*\/\/.*console\.log).*console\.log.*$

var objAtendimento = {};
var savedObjAtendimento = {};
var dcpFileToDelete = [];

var atdId = "";
var readOnly = false;
var isReference = false;
var isLink = false;

var objTerminais = {};
var objProdutos = {};

var objDcp = {};
var objExcel = {};

function updateQrCode() {
    let divNode = document.getElementById("qrcode");
    let qrcodeLink = "";
    
    if( readOnly == false ) {
        qrcodeLink = `${window.location.origin}/atendimentos/link?tag=${objAtendimento.codAtendimento}&tk=${crc32(objAtendimento.codAtendimento+'linkSeed')}`;
    } else {
        qrcodeLink = objAtendimento.link;
    }

    divNode.innerText = qrcodeLink;

    if( qrcodeLink && qrcodeLink.length > 0 ) {
        var qrCodeElm = qrcode(0, 'L');
        qrCodeElm.addData(qrcodeLink,);
        qrCodeElm.make();
        divNode.innerHTML = qrCodeElm.createImgTag();

        if( readOnly == false ) {
            let hrefDivNode = document.getElementById("qrcodelink");
            hrefDivNode.innerText = qrcodeLink;
        }
    }
    //let node = document.createElement("A");
    //node.href = qrcodeLink;
    //node.innerText = qrcodeLink;
    //hrefDivNode.appendChild(node);
}

function disableButton(status = true) {
    //==>trace
    trace_log(1, "disableButton:", status);

    let bt = document.getElementById("salvar");
    if( bt )
        bt.disabled = status;
}

function onUpdateBalancaArqueacao() {
    //==>trace
    trace_log(1,"onUpdateBalancaArqueacao");

    let faltaExcesso = (parseFloat(objAtendimento.arqueacao) - objAtendimento.balanca);

    if (faltaExcesso > 0) {
        objAtendimento.excesso = faltaExcesso;
        objAtendimento.falta = "0.000";
    } else {
        //faltaExcesso = -1 * faltaExcesso;
        objAtendimento.falta = (-1 * faltaExcesso);
        objAtendimento.excesso = "0.000";
    }

    objAtendimento.diferenca = ((faltaExcesso / objAtendimento.balanca) * 100);
    fillGroup1();
}

async function verifyDup(id) {
    trace_log(1, "verifyDup:", id);    

    let objAtd = {};
    let retval = false;
    
    if( id && `${id}`.trim().length > 5 ) {
        try {
            objAtd = await loadAtendimento(id, "codatd");

            debug_log(2, `verifyDup objAtd(${id}):`, objAtd);

            if( objAtd && objAtd.atdId != atdId ) {
                let msg = `cod atendimento '${id}' já está cadastrado no atendimento '${objAtd.atdId}'.\nDeseja carregar este atendimento?`;
                debug_log( 2, msg );

                if (confirm(msg)) {
                    objAtendimento = deepCopyObject(objAtd);
                    showCurrentAtendimento();
                } else {
                    retval = true;
                }
            }

        }catch( e ) {
            let retStr = `verifyDup exception: ${e.stringify()}`;
            debug_log( 2, retStr );
        }
    }
    return retval;
}

function onChangeForm1(evt) {
    //==>trace
    trace_log(1, "onChangeForm1:", evt);
 
    let fldName = evt.target.name.toUpperCase();
    let fldValue = `${evt.target.value}`.trim();

    let pattern = /^([A-Z]+)_?(\d*)_?(\d*)/;
    let fldBaseName = fldName.match(pattern);

    //==>debug
    debug_log(2,"onChangeForm1->fldName:", fldName, "fldBaseName:", fldBaseName, "fldValue:", fldValue);

    if( fldBaseName.length > 1 ) {
        //==>debug
        debug_log(3,"onChangeForm1->fldBaseName[1]:", fldBaseName[1] );

        switch (fldBaseName[1]) {
            case "LINK":
                {
                    objAtendimento.link = fldValue;
                    evt.target.value = objAtendimento.link;
                    break;
                }
                case "CLIENTE":
                {
                    evt.target.value = fldValue;
                    objAtendimento.cliente = fldValue;
                    //==>debug
                    debug_log(2,"objAtendimento.cliente:", objAtendimento.cliente );
                    break;
                }
                case "CODATENDIMENTO":
                {
                    let newValue = fldValue.toUpperCase();

                    if( newValue != objAtendimento.codAtendimento ) {
                        verifyDup(newValue);
                    }
                    
                    objAtendimento.codAtendimento = newValue;
                    evt.target.value = objAtendimento.codAtendimento;

                    updateQrCode();

                    break;
                }
            case "DATA":
                {
                    //
                    //node.addEventListener("focusout", (evt) => {
                    let data = parseDate(evt.target.value);
                    //==>debug
                    debug_log(1,'onChange: data:', data, ' objAtendimento.data:', objAtendimento.data  );

                    if (data == null)
                        evt.target.focus();
                    else {
                        let isoData = data.toISOString().substring(0, 10);
                        
                        if( isoData != objAtendimento.data ) {
                            objAtendimento.data = isoData;
                        }

                        evt.target.value = new Date(data).toLocaleDateString(szCurrentLocale);

                        //if( objAtendimento.data != savedObjAtendimento.data ) {
                        //    //Enable save button
                        //    disableButton(false);                
                        //}

                        //console.log( 'onChange:  evt.target.value:',  evt.target.value, ' objAtendimento.data:', objAtendimento.data  );

                        //console.log( ' evt.target.value:',  evt.target.value  );
                    }

                    //});
                    break;
                }
            case "NAVIO":
                {
                    let newValue = fldValue;

                    if( newValue != objAtendimento.navio ) {
                        objAtendimento.navio = fldValue;
                    }

                    evt.target.value = objAtendimento.navio;

                    //if( objAtendimento.navio != savedObjAtendimento.navio ) {
                    //    //Enable save button
                    //    disableButton(false);                
                    //}
                    break;
                }
            case "BALANCA":
                {
                    let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                
                    //==>debug
                    debug_log(1,"fldValue:", fldValue, "value:", value );

                    if (Number(objAtendimento.balanca) != value) {
                        objAtendimento.balanca = value;
                        onUpdateBalancaArqueacao();
                    }

                    evt.target.value = numeral(objAtendimento.balanca).format('0,0.000');

                    //if( Number(objAtendimento.balanca) != Number(savedObjAtendimento.balanca) ) {
                    //    //Enable save button
                    //    disableButton(false); 
                    //}

                    break;
                }
            case "ARQUEACAO":
                {
                    let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                
                    if (Number(objAtendimento.arqueacao) != Number(value)) {
                        objAtendimento.arqueacao = value;
                        onUpdateBalancaArqueacao();
                    }

                    evt.target.value = numeral(objAtendimento.arqueacao).format('0,0.000');

                    //if( Number(objAtendimento.arqueacao) != Number(savedObjAtendimento.arqueacao) ) {
                    //    //Enable save button
                    //    disableButton(false); 
                    //}
                    break;
                }
            case "COMANDONAVIO":
                {
                    let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                
                    if (Number(objAtendimento.comando_navio) != Number(value)) {
                        objAtendimento.comando_navio = value;
                    }

                    evt.target.value = numeral(objAtendimento.comando_navio).format('0,0.000');

                    //if( Number(objAtendimento.comando_navio) != Number(savedObjAtendimento.comando_navio) ) {
                    //    //Enable save button
                    //    disableButton(false); 
                    //}

                    break;
                }
            case "PERITORECEITA":
                {
                    let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                
                    if (Number(objAtendimento.perito_receita) != Number(value)) {
                        objAtendimento.perito_receita = value;
                    }

                    evt.target.value = numeral(objAtendimento.perito_receita).format('0,0.000');

                    //if( Number(objAtendimento.perito_receita) != Number(savedObjAtendimento.perito_receita) ) {
                    //    //Enable save button
                    //    disableButton(false); 
                    //}

                    break;
                }
            case "OUTRASPARTES":
                {
                    let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                
                    switch (fldName) {
                        case "OUTRASPARTES_1":
                            {
                                if (Number(objAtendimento.outras_partes1) != Number(value)) {
                                    objAtendimento.outras_partes1 = value;
                                }
                
                                evt.target.value = numeral(objAtendimento.outras_partes1).format('0,0.000');
                
                                //if( Number(objAtendimento.outras_partes1) != Number(savedObjAtendimento.outras_partes1) ) {
                                //    //Enable save button
                                //    disableButton(false); 
                                //}
                                break;
                            }
                        case "OUTRASPARTES_2":
                            {
                                if (Number(objAtendimento.outras_partes2) != Number(value)) {
                                    objAtendimento.outras_partes2 = value;
                                }
                
                                evt.target.value = numeral(objAtendimento.outras_partes2).format('0,0.000');
                
                                //if( Number(objAtendimento.outras_partes2) != Number(savedObjAtendimento.outras_partes2) ) {
                                //    //Enable save button
                                //    disableButton(false); 
                                //}
                                break;
                            }
                        case "OUTRASPARTES_3":
                            {
                                if (Number(objAtendimento.outras_partes3) != Number(value)) {
                                    objAtendimento.outras_partes3 = value;
                                }
                
                                evt.target.value = numeral(objAtendimento.outras_partes3).format('0,0.000');
                
                                //if( Number(objAtendimento.outras_partes3) != Number(savedObjAtendimento.outras_partes3) ) {
                                //    //Enable save button
                                //    disableButton(false); 
                                //}
                                break;
                            }
                    }
                    break;
                }
            case "OUTRASPARTESID":
                {
                    let value = fldValue;
                    
                    evt.target.value = fldValue;

                    switch (fldName) {
                        case "OUTRASPARTESID_1":
                            {
                                objAtendimento.outras_partes1_id = fldValue;

                                //if( objAtendimento.outras_partes1_id != savedObjAtendimento.outras_partes1_id ) {
                                //    //Enable save button
                                //    disableButton(false); 
                                //}
                
                                break;
                            }
                        case "OUTRASPARTESID_2":
                            {
                                objAtendimento.outras_partes2_id = fldValue;

                                //if( objAtendimento.outras_partes2_id != savedObjAtendimento.outras_partes2_id ) {
                                //    //Enable save button
                                //    disableButton(false); 
                                //}
                
                                break;
                            }
                        case "OUTRASPARTESID_3":
                            {
                                objAtendimento.outras_partes3_id = fldValue;

                                //if( objAtendimento.outras_partes3_id != savedObjAtendimento.outras_partes3_id ) {
                                //    //Enable save button
                                //    disableButton(false); 
                                //}
                
                                break;
                            }
                    }
                    break;
                }
            case "PORAOSELECT":
                {
                    //==>debug
                    debug_log(1,"onChangeForm1->PORAOSELECT->fldName:", fldName, "fldValue:", fldValue, "length:", evt.target.options.length);

                    for (let i = 0; i < evt.target.options.length; i++) {
                        if (evt.target.options[i].selected) {

                            let pattern = /^([A-Za-z]+)_?(\d*)_?(\d*)/;
                            let porProd = evt.target.options[i].name.match(pattern);

                            //==>debug
                            debug_log(1,"onChangeForm1->porProd", porProd, "objAtendimento.poroes", objAtendimento.poroes);

                            //if (!objAtendimento.poroes)
                            //    objAtendimento.poroes = {};

                            //if (!(porProd[2] in objAtendimento.poroes) ) {
                            //    objAtendimento.poroes[porProd[2]] = {
                            //        "produto_id": 0,
                            //        "terminais": {},
                            //        "cubagem": 0,
                            //        "fatorestiva": 0.00,
                            //        "condicao": "---"
                            //    };
                            //}

                            objAtendimento.poroes[porProd[2]].produto_id = Number(porProd[3]);

                            //if( !("produto_id" in savedObjAtendimento.poroes[porProd[2]]) || 
                            //    objAtendimento.poroes[porProd[2]].produto_id != savedObjAtendimento.poroes[porProd[2]].produto_id ) {
                            //    //Enable save button
                            //    disableButton(false);  
                            //}
                        }
                    }
                    break;
                }
            case "QTDPT":
                {
                    let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));

                    //==>debug
                    debug_log(1,"onChangeForm1->QTDPT->fldName:", fldName, "value:", value);

                    //if (!("poroes" in objAtendimento)) {
                    //    objAtendimento.poroes = {};
                    //}

                    //==>debug
                    debug_log(1,`objAtendimento.poroes[${fldBaseName[2]}]:`, objAtendimento.poroes[fldBaseName[2]] );

                    //if (!(fldBaseName[2] in objAtendimento.poroes) ) {
                    //    objAtendimento.poroes[fldBaseName[2]] = {
                    //        "produto_id": 0,
                    //        "terminais": {},
                    //        "cubagem": 0,
                    //        "fatorestiva": 0.00,
                    //        "condicao": "---"
                    //    };
                    //}

                    //if (!("terminais" in objAtendimento.poroes[fldBaseName[2]])) {
                    //    objAtendimento.poroes[fldBaseName[2]].terminais = {};   
                    //}

                    if( Number(value) == 0 ) {
                        delete objAtendimento.poroes[fldBaseName[2]].terminais[fldBaseName[3]];

                        if (fldBaseName[3] in savedObjAtendimento.poroes[fldBaseName[2]].terminais) {
                            //Enable save button
                            disableButton(false);
                        }

                    } else {
                        if ( Number(value) != 
                            Number(objAtendimento.poroes[fldBaseName[2]].terminais[fldBaseName[3]])) {
                            objAtendimento.poroes[fldBaseName[2]].terminais[fldBaseName[3]] = {
                                "quantidade": value
                            };
                        }

                        //if ( !(fldBaseName[3] in savedObjAtendimento.poroes[fldBaseName[2]].terminais) ||
                        //    objAtendimento.poroes[fldBaseName[2]].terminais[fldBaseName[3]].quantidade != 
                        //    savedObjAtendimento.poroes[fldBaseName[2]].terminais[fldBaseName[3]].quantidade) {
                        //    //Enable save button
                        //    disableButton(false);  
                        //}
                    }

                    //==>debug
                    debug_log(1,`objAtendimento.poroes[${fldBaseName[2]}].terminais:`, objAtendimento.poroes[fldBaseName[2]].terminais );

                    fillDcpTable();

                    break;
                }
            case "CUBPORAO":
                {
                    let value = Number(fldValue.replace(/\./g, "").replace(/,/g, "\."));
                    
                    //==>debug
                    debug_log(1,"onChangeForm1->CUBPORAO->fldName:", fldName, "value:", value);

                    //if (!("poroes" in objAtendimento))
                    //    objAtendimento.poroes = {};
                    //
                    //if (!(fldBaseName[2] in objAtendimento.poroes)) {
                    //    objAtendimento.poroes[fldBaseName[2]] = {
                    //        "produto_id": 0,
                    //        "terminais": {},
                    //        "cubagem": 0,
                    //        "fatorestiva": 0.00,
                    //        "condicao": "---"
                    //    };
                    //}
                    //console.log(`savedObjAtendimento.poroes[${fldBaseName[2]}].cubagem:`, savedObjAtendimento.poroes[fldBaseName[2]].cubagem);

                    objAtendimento.poroes[fldBaseName[2]].cubagem = value;

                    //if( objAtendimento.poroes[fldBaseName[2]].cubagem != savedObjAtendimento.poroes[fldBaseName[2]].cubagem) {
                    //    //Enable save button
                    //    disableButton(false);                        
                    //}

                    fillDcpTable();

                    break;
                }
            case "COND":
                {
                    //==>debug
                    debug_log(1,"onChangeForm1->COND->fldName:", fldName, "value:", fldValue);

                    //if (!("poroes" in objAtendimento))
                    //    objAtendimento.poroes = {};
                    //
                    //if (!(fldBaseName[2] in objAtendimento.poroes)) {
                    //    objAtendimento.poroes[fldBaseName[2]] = {
                    //        "produto_id": 0,
                    //        "terminais": {},
                    //        "cubagem": 0,
                    //        "fatorestiva": 0.00,
                    //        "condicao": "---"
                    //    };
                    //}

                    objAtendimento.poroes[fldBaseName[2]].condicao = fldValue;

                    //if( objAtendimento.poroes[fldBaseName[2]].condicao != savedObjAtendimento.poroes[fldBaseName[2]].condicao) {
                    //    //Enable save button
                    //    disableButton(false);                        
                    //}

                    fillDcpTable();

                    break;
                }
            default:
                {
                    let className = evt.target.className.toUpperCase();

                    //==>debug
                    debug_log(3,"onChangeForm1->default->fldName:", fldName, 
                        "fldBaseName:", fldBaseName, "fldValue:", fldValue, 
                        "className:", className, "evt.target", evt.target);

                    switch (className) {
                        case "CBPOROES":
                            {
                                let cbPoroes = document.querySelectorAll(".cbPoroes");

                                objAtendimento.lstPoroes = [];

                                if (!("poroes" in objAtendimento))
                                    objAtendimento.poroes = {};

                                let prodId = (objAtendimento.lstProdutos.length == 1) ? objAtendimento.lstProdutos[0] : "0";

                                cbPoroes.forEach(i => {

                                    //==>debug
                                    debug_log(3,"onChangeForm1->default->i:", i.id, i.checked);

                                    if (i.checked) {
                                        if ( !(i.id in objAtendimento.poroes)) {
                                            if ( i.id in savedObjAtendimento.poroes ) {
                                                objAtendimento.poroes[i.id] = deepCopyObject(savedObjAtendimento.poroes[i.id]);
                                            } else {
                                                objAtendimento.poroes[i.id] = {
                                                    "produto_id": prodId,
                                                    "terminais": {},
                                                    "cubagem": 0,
                                                    "fatorestiva": 0.00,
                                                    "condicao": "---"
                                                };
                                            }
                                        }

                                        objAtendimento.lstPoroes.push(i.value);
                                    } else {
                                        if ( i.id in objAtendimento.poroes ) {
                                            delete objAtendimento.poroes[i.id];
                                        }
                                    }
                                });

                                fillDcpTable();

                                break;
                            }
                        case "CBTERMINAIS":
                            {
                                let cbTerminais = document.querySelectorAll(".cbTerminais");

                                //==>debug
                                debug_log(1,'cbTerminais:', cbTerminais );
                                debug_log(1,'objAtendimento.lstTerminais:', objAtendimento.lstTerminais );
                                debug_log(1,'objAtendimento.terminais:', objAtendimento.terminais );

                                objAtendimento.lstTerminais = [];

                                if (!("terminais" in objAtendimento))
                                    objAtendimento.terminais = [];

                                cbTerminais.forEach(i => {
                                    if (i.checked) {
                                        if (!objAtendimento.terminais.includes(Number(i.id))) {
                                            objAtendimento.terminais.push(Number(i.id));
                                        }

                                        objAtendimento.lstTerminais.push(Number(i.id));

                                //        if( false == savedObjAtendimento.terminais.includes(Number(i.id)) ) {
                                //            //Enable save button
                                //            disableButton(false);                                
                                //        }                                    
                                //    } else {
                                //        if( true == savedObjAtendimento.terminais.includes(Number(i.id)) ) {
                                //            //Enable save button
                                //            disableButton(false);                                
                                //        }
                                    }
                                });

                                //==>debug
                                debug_log(1,'objAtendimento.lstTerminais:', objAtendimento.lstTerminais );
                                debug_log(1,'objAtendimento.terminais:', objAtendimento.terminais );

                                fillDcpTable();
                                break;
                            }
                        case "CBPRODUTOS":
                            {
                                let cbProdutos = document.querySelectorAll(".cbProdutos");

                                //==>debug
                                debug_log(1,'cbProdutos:', cbProdutos );
                                debug_log(1,'objAtendimento.lstProdutos:', objAtendimento.lstProdutos );
                                debug_log(1,'objAtendimento.produtos:', objAtendimento.produtos );

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
                                        
                                //        if( false == savedObjAtendimento.lstProdutos.includes(Number(i.id)) ) {
                                //            //Enable save button
                                //            disableButton(false);                                
                                //        }                                     
                                //    } else {
                                //        if( true == savedObjAtendimento.lstProdutos.includes(Number(i.id)) ) {
                                //            //Enable save button
                                //            disableButton(false);                                
                                //        }
                                    }
                                });

                                //==>debug
                                debug_log(1,'objAtendimento.lstProdutos:', objAtendimento.lstProdutos );
                                debug_log(1,'objAtendimento.produtos:', objAtendimento.produtos );

                                fillDcpTable();
                                break;
                            }
                        case "CBDCPFILES":
                            {
                                //==>debug
                                debug_log(1,"onChangeForm1->default->CBDCPFILES:" );

                                let bt = document.getElementById("fileDelete");
                                let cbDcpFiles = document.querySelectorAll(".cbDcpFiles");
                            
                                dcpFileToDelete = [];
                            
                                cbDcpFiles.forEach(i => {
                                    if (i.checked) {
                                        dcpFileToDelete.push(i.id);
                                    }
                                });
                            
                                if (dcpFileToDelete.length > 0) {
                                    bt.disabled = false;
                                } else {
                                    bt.disabled = true;
                                }

                                break;
                            }

                    }
                    break;
                }

        }
    }  
    
    debug_log( 2, "onChangeForm1:", "objAtendimento:", objAtendimento );
    debug_log( 2, "onChangeForm1:", "savedObjAtendimento:", savedObjAtendimento );
    
    let cmp = cmpAtendimentoDcp(objAtendimento, savedObjAtendimento);
    //==>debug
    debug_log( 2, "onChangeForm1:", "atdId:", atdId, "cmpAtendimentoDcp(objAtendimento, savedObjAtendimento):", cmp.toString(2).padStart(8,'0') );

    //if( isReference && (objAtendimento.codAtendimento.length < 5 || objAtendimento.cliente.length < 1 ) )
    //    disableButton(true);
    //else {
    if ( atdId == 0 || cmp > 0 ) {
        disableButton(false);
    } else {
        disableButton(true);
    }
}

function onReloadDcpXls(idext) {
    //==>trace
    trace_log(1,"onReloadDcpXls:", idext);
    debug_log( 3, `objAtendimento.lstPlanos: ${objAtendimento.lstPlanos}, idext:${idext}`);

    let startPos = idext.indexOf('.')>=0?idext.indexOf('.')+1:0;
    let ext = idext.substring(startPos);

    if( (objAtendimento.lstPlanos && objAtendimento.lstPlanos.length == 0) || 
        !(objAtendimento.lstPlanos.includes(ext)) )
        return;

        
    let headers = new Headers();
    headers.append('Accept', 'application/json');

    fetch(`/atendimentos/api/showexcel.php?mode=json&filename=${idext}`, {
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
        objExcel = deepCopyObject(json);

        //==>debug
        debug_log(1,"objExcel:", objExcel);

        objDcp = convExcel2Json(objExcel);
        updateJsonAtd(objDcp);

        //==> debug
        debug_log(2, "onReloadDcpXls->objDcp:", objDcp);

        let errorMsg = setAtendimentoDcp(objAtendimento, objDcp);

        if( errorMsg.length > 0 ) {
            writeToConsole(errorMsg, 60000 );
            //==> debug
            debug_log(2, "onReloadDcpXls->setAtendimentoDcp->errorMsg:", errorMsg);
        }

        fillGroup1();
        fillDcpTable();

        let cmp = cmpAtendimentoDcp(objAtendimento, savedObjAtendimento);
        if( cmp > 0 ) writeToConsole(cmpAtendimentoDcp(objAtendimento, savedObjAtendimento, true), 60000 );
                                    
        if (atdId == 0 || cmp > 0) {
            disableButton(false);
        } else {
            disableButton(true);
        }

    })
    .catch(err => {
        let error = `catch: fetch onReloadDcpXls: ${err.message}`;
        writeToConsole(error);
    })
}

// onLoadDcpFiles()
function onLoadDcpFiles() {
    //==>trace
    trace_log(1,"onLoadDcpFiles:");

    const files = document.getElementById("planodecarga").files;

    //==>debug
    debug_log(1, "onLoadDcpFiles->files:", files);
    
    (document.getElementById("processDcpFile")).disabled = true;

    if (Object.keys(files).length > 0) {
        Object.keys(files).forEach(i => {
            let file = files[i];
    
            if (file.name.indexOf("xls") > 0) {
                (document.getElementById("processDcpFile")).disabled = false;
            }
        });
    }

    let cmp = cmpAtendimentoDcp(objAtendimento, savedObjAtendimento);
    
    //==>debug
    debug_log( 2, "onLoadDcpFiles:", "cmpAtendimentoDcp(objAtendimento, savedObjAtendimento):", cmp.toString(2).padStart(8,'0')  );
    
    if (atdId == 0 || cmp > 0) {
        disableButton(false);
    } else {
        disableButton(true);
    }
}

function onProcessDcpFiles() {
    //==>trace
    trace_log(1,"onProcessDcpFiles:");

    writeToConsole("processando...");

    const files = document.getElementById("planodecarga").files;

    Object.keys(files).forEach(i => {
        let file = files[i];

        if (file.name.indexOf("xls") > 0) {

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
                debug_log(1,'onProcessDcpFiles(objDcp):', objDcp);
                
                updateJsonAtd(objDcp);

                //if( 
                verifyDup(objDcp.codAtendimento); // == false ) {

                let errorMsg = setAtendimentoDcp(objAtendimento, objDcp);
                if( errorMsg.length > 0 ) writeToConsole(errorMsg, 60000 );
    
                fillGroup1();
                fillDcpTable();
                
                let cmp = cmpAtendimentoDcp(objAtendimento, savedObjAtendimento);
                if( cmp > 0 ) writeToConsole(cmpAtendimentoDcp(objAtendimento, savedObjAtendimento, true), 60000 );
                                    
                if (atdId == 0 || cmp > 0) {
                    disableButton(false);
                } else {
                    disableButton(true);
                }
                //}    

                return objDcp;
            })
            .catch(err => {
                let error = `catch: fetch objDcp: ${err.message}, ${JSON.stringify(err)}`;
                writeToConsole(error);
            })
        }
    });
}

function getOutrasPartesOption(objAtendimento, fSelected = false, value) {
    //==>debug
    debug_log(3,"getOutrasPartesOption:", objAtendimento, fSelected, value );

    let optNode = document.createElement('OPTION');
    optNode.value = value;
    optNode.selected = fSelected;
    optNode.innerHTML = value;
    return optNode;
}

// Main App
function crateMainApp() {
    //==>trace
    trace_log(1,"crateMainApp:" );

    const mainApp = document.getElementById("MainApp");
    mainApp.innerText = "";

    if( isReference == false ) {
        if (readOnly == false) {
            const frmForm = document.createElement("FORM");
            frmForm.id = "formDCP";
            frmForm.enctype = "multipart/form-data";
        
            // Plano de carga detalhado
            let divNode = document.createElement("DIV");
            divNode.innerHTML = '<label for="planodecarga">Adicionar Arquivos:</label>';
            
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
            frmForm.appendChild(divNode);
            mainApp.appendChild(frmForm);
        }
    }
    
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

        let divNode = document.createElement("DIV");
        divNode.id = "load-dcp-ext";
        frmForm1.appendChild(divNode);

        // Remove saved dcp files
        divNode = document.createElement("DIV");
        divNode.id = "cbDcpFiles";
        frmForm1.appendChild(divNode);        
    }

    //let fieldSet = document.createElement("FIELDSET");
    //let legend = document.createElement("LEGEND");
    //legend.innerText = `Atd id: ${atdId>0?atdId:'New'}`;
    //fieldSet.appendChild(legend);
   
    //Atendimento Id
    let divNode = document.createElement("DIV");
    divNode.classList.add("text-blue-700");
    divNode.innerText = `Atd Id: ${atdId>0?atdId:'New'}`
    frmForm1.appendChild(divNode);

    if (readOnly == false) {        
        // button
        let node = document.createElement("INPUT");
        node.type = "button";
        node.id = "salvar";
        node.disabled = true;

        if (atdId && Number(atdId) > 0) {
            node.value = "Save";
        }else {
            node.value = "Add New";
        }

        node.onclick = (e) => onSaveForm1(e);

        frmForm1.appendChild(node);

        //<p id="writeToconsole"></p>
        let pNode = document.createElement("P");
        pNode.id = "writeToconsole";

        frmForm1.appendChild(pNode);
    }

    // Group 1
    let fldSet = document.createElement("fieldset");
    fldSet.id = "group1";
    fldSet.innerText = "";
    fldSet.classList.add(".noBorder");

    frmForm1.appendChild(fldSet);

    //fldSet.oninput = function (evt) { return onInputGroup1(evt); };
    //fldSet.onchange = function (evt) { return onChangeGroup1(evt); };

    /*
    divNode = document.createElement("DIV");
    divNode.id = "group1";
    fldSet.appendChild(divNode);
    */

    if( isReference == false ) {
        // dcp table
        fldSet = document.createElement("fieldset");
        fldSet.id = "fldSetDcpTable";
        //fldSet.onChange = function (evt) { return onChangeDcp(evt); };

        divNode = document.createElement("DIV");
        divNode.id = "dcpTable";

        fldSet.appendChild(divNode);
        frmForm1.appendChild(fldSet);
    }

    mainApp.appendChild(frmForm1);
}

function fillDeleteFileList() {
    //==>trace
    trace_log(1,"fillDeleteFileList:" );

    if (readOnly == false) {
        const divNode = document.getElementById("cbDcpFiles");
        divNode.innerHTML = `<label for="dcpFileToDelete">Delete files:</label>`

        const loadDcpExtNode = document.getElementById("load-dcp-ext");
        loadDcpExtNode.innerHTML = "";
    
        if( "lstPlanos" in objAtendimento && objAtendimento.lstPlanos.length > 0 ) {
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

            // load saved dcp files
            objAtendimento.lstPlanos.forEach(ext => {
                if (ext.toUpperCase().substring(0, 3) == "XLS") {
                    let node = document.createElement('INPUT');
                    node.type = "button";
                    node.id = `${objAtendimento.atdId}.${ext}`;
                    node.value = `load DCP (${ext})`;
                    node.onclick = function(evt) {
                        //==>debug
                        debug_log(1,'button onclick', evt.target);
                        onReloadDcpXls(evt.target.id);
                    };
                    loadDcpExtNode.appendChild(node);
                }
            });
        }
    }
}

function fillGroup1() {
    //==>trace
    trace_log(1,"fillGroup1:" );

    //==>debug
    debug_log(1,'fillGroup1() objAtendimento:', objAtendimento, 'atdId:', atdId, 'readOnly:', readOnly);

    try {
        let divGroup1 = document.getElementById("group1");
        divGroup1.innerHTML = "";
        let divNode;
        let node;
        
        var tbNode = document.createElement("table");
        var trNode = document.createElement("tr");
        var tdNode = document.createElement("td");
        tdNode.style="text-align: left; vertical-align: middle; border: 0;"

        trNode.appendChild(tdNode);
        tbNode.appendChild(trNode);
        divGroup1.appendChild(tbNode);
            
        if( isLink == true ) {
            // certificate view button
            // "<button type=\"button\" onclick=\"openModalWindow('planos/plano_de_carga".${objAtendimento.id}.".pdf?q=".microtime(true)."')\">".$matches[count($matches)-1]."</button>";
            // <button type="button" onclick="openModalWindow('planos/plano_de_carga${}.pdf?q=1755271016.5735')">pdf</button>
            let divNode = document.createElement("DIV");
            let node = document.createElement("INPUT");

            node.type = "button";
            node.id = "certificate";
            node.disabled = true;
            node.value = "Certificado";
            
            if( "lstPlanos" in objAtendimento && objAtendimento.lstPlanos.length > 0 && objAtendimento.lstPlanos.includes('pdf')) {
                node.disabled = false;
                node.onclick = (e) => openModalWindow(`planos/plano_de_carga${objAtendimento.atdId}.pdf?q=${new Date().getTime()}`);
            }

            divNode.appendChild(node);
            tdNode.appendChild(divNode);
        }

        //<input name="link" type="text" id="link" size="80" maxlength="512" />
        divNode = document.createElement("DIV");

        if( readOnly ) {
            if( objAtendimento.link && `${objAtendimento.link}`.trim().length > 0 ) {
                divNode.innerHTML = `<label>Link to files:</label><sp><a href="${objAtendimento.link}">${objAtendimento.link}</a>`
            } else {
                divNode.innerHTML = "<b><i>The link to the files has expired !</i></b>";    
            }
            tdNode.appendChild(divNode);

            //divGroup1.appendChild(divNode);
        } else {
            divNode.innerHTML = `<label for="link">Link:</label>`;

            node = document.createElement("INPUT");
            node.name = "link";
            node.type = "text";
            node.id = "link";
            node.size = "50";
            node.maxlength = "512";
            node.value = objAtendimento.link;
            node.readOnly = readOnly;
            node.oninput = function(evt) {
                evt.target.value = evt.target.value;
            };
        
            divNode.appendChild(node);
            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);
        }

        //<input name="codAtendimento" type="text" id="codAtendimento" size="15" maxlength="20" />
        divNode = document.createElement("DIV");
        divNode.innerHTML = `<label for="codAtendimento">Cod.Atendimento:</label>`

        node = document.createElement("INPUT");
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
        node.required = isReference;

        divNode.appendChild(node);

        tdNode.appendChild(divNode);
        //divGroup1.appendChild(divNode);

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
        
        tdNode.appendChild(divNode);
        //divGroup1.appendChild(divNode);

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
        
        tdNode.appendChild(divNode);
        //divGroup1.appendChild(divNode);

        if( isReference == false ) {
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
            
            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);

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
            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);

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
            
            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);

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
            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);

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

            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);

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

            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);

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

            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);

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
            
            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);

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
            
            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);

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
            
            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);


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
                    debug_log(3,'fillGroup1(cbPoroes) value:', value, 
                        'Number(value):', objAtendimento.lstPoroes.includes(Number(value)),
                        'String(value):', objAtendimento.lstPoroes.includes(String(value)),
                        );

                    if (objAtendimento.lstPoroes.includes(String(value)) || 
                        objAtendimento.lstPoroes.includes(Number(value)) )
                        node.checked = true;
                    else
                        node.checked = false;

                    divNode.appendChild(node);
                    divNode.append(value);
                };

                tdNode.appendChild(divNode);
                //divGroup1.appendChild(divNode);

                //<div id="produtos_checkbox"></div>
                divNode = document.createElement("DIV");
                divNode.id = "produtosCheckbox";
                divNode.innerHTML = '<label for="produtosId">Produtos:</label>';

                //==>debug
                debug_log(3,'fillGroup1(cbProdutos) objProdutos:', objProdutos);

                Object.keys(objProdutos).forEach(value => {
                    if( Number(value) > 0 ) {
                        const node = document.createElement('INPUT');
                        node.type = "checkbox";
                        node.name = `produtosId_${value}`;
                        node.id = value;
                        node.value = objProdutos[value].nome;
                        node.className = "cbProdutos";

                        //==>debug
                        debug_log(3,'fillGroup1(cbProdutos) value:', value, 
                            'Number(value):', objAtendimento.lstProdutos.includes(Number(value)),
                            'String(value):', objAtendimento.lstProdutos.includes(String(value)),
                            );

                        if (objAtendimento.lstProdutos.includes(Number(value)) ||
                            objAtendimento.lstProdutos.includes(String(value)) ) {
                            node.checked = true;
                        } else {
                            node.checked = false;
                        }

                        divNode.appendChild(node);
                        divNode.append(objProdutos[value].nome);
                    }
                });

                tdNode.appendChild(divNode);
                //divGroup1.appendChild(divNode);

                //<div id="terminais_checkbox"></div>
                divNode = document.createElement("DIV");
                divNode.id = "terminaisCheckbox";
                divNode.innerHTML = '<label for="terminaisId">Terminais:</label>';

                //==>debug
                debug_log(3,'fillGroup1(cbTerminais) objTerminais:', objTerminais);

                Object.keys(objTerminais).forEach(value => {
                    if( Number(value) > 0 ) {
                        const node = document.createElement('INPUT');
                        node.type = "checkbox";
                        node.name = `terminaisId_${value}`;
                        node.id = value;
                        node.value = objTerminais[value].nome;

                        node.className = "cbTerminais";

                        //==>debug
                        debug_log(3,'fillGroup1(cbTerminais) value:', value, 
                            'Number(value):', objAtendimento.lstTerminais.includes(Number(value)),
                            'String(value):', objAtendimento.lstTerminais.includes(String(value)),
                            );

                        if (objAtendimento.lstTerminais.includes(Number(value)) ||
                            objAtendimento.lstTerminais.includes(String(value)) )
                            node.checked = true;
                        else
                            node.checked = false;

                        divNode.appendChild(node);
                        divNode.append(objTerminais[value].nome);
                    }
                });

                tdNode.appendChild(divNode);
                //divGroup1.appendChild(divNode);
            }
        } else {
            //<input name="cliente" type="text" id="cliente"  size="30" maxlength="60" required/>
            divNode = document.createElement("DIV");
            divNode.innerHTML = `<label for="cliente">Cliente:</label>`
            node = document.createElement("INPUT");
            node.name = "cliente";
            node.type = "text";
            node.id = "cliente";
            node.size = "30";
            node.maxlength = "60";
            if( readOnly == false ) {
                node.required = true;
            }
            node.readOnly = readOnly;
            node.value = objAtendimento.cliente?objAtendimento.cliente:"";

            //node.oninput = function(evt) {
            //    if( evt.target.value.length > 0 )
            //        evt.target.value = evt.target.value.toUpperCase();
            //};
            divNode.appendChild(node);
            tdNode.appendChild(divNode);
            //divGroup1.appendChild(divNode);        
        }

        tdNode = document.createElement("td");
        tdNode.style="text-align: center; vertical-align: middle; border: 0;"
        trNode.appendChild(tdNode);

        divNode = document.createElement("DIV");
        divNode.id = "qrcode";
        tdNode.appendChild(divNode);

        divNode = document.createElement("DIV");
        divNode.id = "qrcodelink";
        tdNode.appendChild(divNode);

        updateQrCode();

    } catch (error) {
        writeToConsole(`exception ${error} in fillGroup1`);
        console.error(`exception ${error} in fillGroup1`, error);
    }
}

// Create the DCP table
function fillDcpTable() {
    //==>trace
    trace_log(1,"fillDcpTable:" );

    //==>debug
    debug_log(2,'fillDcpTable->objAtendimento:', objAtendimento);

    writeToConsole();

    if( objAtendimento.lstPoroes.length > 0 ) {
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

                    //==>debug
                    debug_log(3,`fillDcpTable->objProdutos:`, objProdutos);

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

                                if (objTerminais[termId].descricao.trim().length > 3 &&
                                    objTerminais[termId].descricao.substring(0, 3) == "(-)" &&
                                    parseFloat(objAtendimento.poroes[poraoId].terminais[termId].quantidade) > 0)
                                    objAtendimento.poroes[poraoId].terminais[termId].quantidade *= -1;

                                value = parseFloat(objAtendimento.poroes[poraoId].terminais[termId].quantidade);

                                termTotal += value;

                                if (!poraoTotal[poraoId])
                                    poraoTotal[poraoId] = +0.0;

                                poraoTotal[poraoId] += value;

                                //==>debug
                                debug_log(4,"fillDcpTable:", 'value', value, 'termTotal', termTotal, `poraoTotal[${poraoId}]`, poraoTotal[poraoId] );
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

                objAtendimento.balanca = totalTotal;
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

                    ["SLACK","FULL","TRANSIT","EMPTY","TOP OFF"].forEach( v => {
                        optNode = document.createElement("OPTION");
                        optNode.value = v;
                        optNode.innerText = optNode.value;
        
                        if (objAtendimento.poroes[poraoId].condicao == optNode.value)
                            optNode.selected = true;
        
                        selNode.appendChild(optNode);
                    });
                    
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
            console.error(`exception ${error} in fillDcpTable`, error);
        }
    }
}

// Convert excel to json
function convExcel2Json(objExcel) {
    //==>trace
    trace_log(1,"convExcel2Json:", objExcel );

    let objDcp = createNewAtendimento();
    objDcp.atdId = 0;

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
            debug_log(1,"objExcel: sItem:", sItem);

            if (sItem.length == 1 && lineType == 0) {
                objDcp["codAtendimento"] = sItem[0].value;
            }

            if (sItem.length > 1 && sItem[0].type) {

                let tag = sItem[0].tag;

                //==>debug
                debug_log(3,"tag:", tag);

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
                        debug_log(1,"dataDate:", dataDate, ", sItem[1].value:", sItem[1].value );

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
                        objDcp["balanca"] = sItem[1].value;
                        lineType |= pesoterra;
                        found = true;
                    }

                    if (!found && (lineType & pesobordo) == 0 && tag == "pesobordo") {
                        objDcp["arqueacao"] = sItem[1].value;
                        lineType |= pesobordo;
                        found = true;
                    }

                    if (!found && (lineType & variacao) == 0 && tag == "variacao") {
                        objDcp["diferenca"] = sItem[1].value;
                        lineType |= variacao;
                        found = true;
                    }

                    if (!found && (lineType & porao) == 0 && tag == "porao") {
                        //colIni = sItem[1].c;

                        //==>debug
                        debug_log(1,"porao sItem:", sItem);

                        for (let i = 1; i < sItem.length; i++) {

                            let value = parseInt(sItem[i].value);

                            //==>debug
                            debug_log(1,`porao sItem[${i}]}:`, sItem[i], "value:", value, "(value > 0) && (value < 10):", (value > 0) && (value < 10));
                            
                            if ((value > 0) && (value < 10)) {

                                //==>debug
                                debug_log(1,`!('${sItem[i].c}' in dctPores)`, !(`'${sItem[i].c}'` in dctPores));

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
                        debug_log(1,'porao (2) objDcp["poroes"]:', objDcp["poroes"]);
                        debug_log(1,"porao (2) objDcp.lstPoroes:", objDcp.lstPoroes);
                        debug_log(1,"porao (2) dctPores:", dctPores);

                        lineType |= porao;
                        found = true;
                    }

                    if (!found && (lineType & produto) == 0 && tag == "produto") {

                        //==>debug
                        debug_log(1, 'tag == produto');

                        //let colIni = sItem[0].c + 1;

                        for (let i = 1; i < sItem.length; i++) {
                            let prodId = (sItem[i].tag in objProdutos["tags"]) ? Number(objProdutos["tags"][sItem[i].tag]) : 0;

                            //==>debug
                            debug_log(1,`sItem[${i}].tag:`, sItem[i].tag, "prodId:", prodId);

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
                                if (sItem[i].tag.length > 0 && sItem[i].tag != "empty") {
                                    const errMsg = `[Produto desconhecido: ${sItem[i].tag}]`;
                                    errorMessage += errMsg;
                                    console.error( `convExcel2Json: ${errMsg}` );
                                }
                            }
                        }

                        lineType |= produto;
                        found = true;
                    }

                    if (!found && (lineType & total) == 0 && tag == "total") {
                        //==>debug
                        debug_log(1,tag, sItem);

                        lineType |= total;
                        found = true;
                    }

                    if (!found && (lineType & cubagem) == 0 && tag == "cubagem") {
                        //==>debug
                        debug_log(1,tag, sItem);

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
                        debug_log(1,tag, sItem);

                        for (let i = 1; i < sItem.length; i++) {
                            if (parseFloat(sItem[i].value) > 0 && (`'${sItem[i].c}'` in dctPores)) {
                                let poraoId = dctPores[`'${sItem[i].c}'`];
                                if (poraoId in objDcp.poroes)
                                    objDcp.poroes[poraoId]["fatorestiva"] = parseFloat(sItem[i].value);
                            }
                        }

                        lineType |= fatorestiva;
                        found = true;
                    }

                    if (!found && (lineType & condicao) == 0 && tag.substring(0,8) == "condicao") {

                        //==>debug
                        debug_log(1,tag, sItem);

                        for (let i = 1; i < sItem.length; i++) {

                            //==>debug
                            debug_log(1,"sItem[i]", sItem[i]);

                            if (sItem[i].value.length > 0 && (`'${sItem[i].c}'` in dctPores)) {
                                let poraoId = dctPores[`'${sItem[i].c}'`];
                                if (poraoId in objDcp.poroes)
                                    objDcp.poroes[poraoId]["condicao"] = sItem[i].value.toUpperCase().trim();

                                //&& (sItem[i].value.toUpperCase() == "FULL" || sItem[i].value.toUpperCase() == "SLACK" ))
                            }
                        }

                        lineType |= condicao;
                        found = true;
                    }

                    if (!found && ((lineType & allLines) != 0)) {
                        //==>debug
                        debug_log(1,tag, sItem);

                        if( tag in objTerminais["tags"] ) {
                            let termId = Number(objTerminais["tags"][tag]);
                            //let colIni = sItem[0].c + 1;
                            
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

                                    objDcp.poroes[poraoId]["terminais"][termId] = { "quantidade": value };
                                }
                            }

                            found = true;
                        } else {
                            const errMsg = `[Terminal desconhecido: ${tag}]`
                            errorMessage += errMsg;
                            console.error( `convExcel2Json: ${errMsg}` );
                        }
                    }
                }
            }
        });

        debug_log( 2, "objDcp (excel):", objDcp);

    } catch (error) {
        let errorMsg = `[exception ${error} in convExcel2Json. lineType: ${lineType.toString(2)}]`;
        errorMessage += errorMsg;

        console.error(errorMsg, error);
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
        console.error( `[convExcel2Json. req fields missing: ${missing}]` );
    }

    //==>debug
    debug_log(1,'objDcp(convExcel2Json):', objDcp);

    if (errorMessage.length > 0)
        setTimeout(writeToConsole, 10000, errorMessage);
    else
        writeToConsole();

    return objDcp;
}

async function onDeleteFile(evt) {
    //==>trace
    trace_log(1,"onDeleteFile:", evt);

    //==>debug
    debug_log(2,"onDeleteFile evt.target:", evt.target)
    writeToConsole("Processando...");

    if (dcpFileToDelete.length > 0) {
        //==>debug
        debug_log(2,"onDeleteFile ctdFiles:", dcpFileToDelete.length);

        let headers = new Headers();
        headers.append('Accept', 'application/json');
        headers.append("Content-type", "application/json");

        try {
            let response =  await fetch(`/atendimentos/api/planos.php?id=${atdId}`, {
                headers: headers,
                mode: 'cors', // no-cors, *cors, same-origin
                body: JSON.stringify(dcpFileToDelete),
                method: 'DELETE', // *GET, POST, PUT, DELETE, etc.
                cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                credentials: 'same-origin', // include, *same-origin, omit
                redirect: 'follow', // manual, *follow, error
                referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            });

            let json = await response.json();

            //==>debug
            debug_log(1,"response onDeleteFile:", json);
            if( "fileList" in json ) objAtendimento.lstPlanos = _copyRecursive(json["fileList"]);
        }catch(err){
            let error = `onDeleteFile->catch: ${JSON.stringify(err)}`;
            writeToConsole(error);
        }
    }

    fillDeleteFileList();
}

async function sendJson(json, apiURL) {
    //==>trace
    trace_log(1,"sendJson:", json, apiURL );

    let id = (json && json.atdId) ? json.atdId : 0;
    let action = (id == 0) ? "POST" : "PUT";

    //==>debug
    debug_log(1,"sendJson json:", json);
    debug_log(1,"sendJson apiURL:", apiURL);

    let headers = new Headers();

    headers.append('Accept', 'application/json');
    headers.append("Content-type", "application/json");

    const response = await fetch( apiURL, {
        headers: headers,
        mode: 'cors', // no-cors, *cors, same-origin
        body: JSON.stringify(json),
        method: action, // *GET, POST, PUT, DELETE, etc.
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
    });

    //==>debug
    debug_log(2,"sendJson response:", response);

    if( response.ok ) {
        return {
            "error": false,
            "response": await response.json(), 
        };
    } else {     
        return {
            "error": true,
            "response": response,
        }; 
    }
}

async function sendFiles(files, apiURL) {
    //==>trace
    trace_log(1,"sendFiles:", files, apiURL );

    //==>debug
    debug_log(1,"sendFiles files:", files, " apiURL:", apiURL );

    return new Promise( async (resolve, reject) => {
        
        let ctdFiles = files.length;

        if (ctdFiles > 0) {
            let headers = new Headers();
            let formData = new FormData();

            // Read selected files
            for (var index = 0; index < ctdFiles; index++) {
                //==>debug
                debug_log(1,"sendFiles->index:", index, files[index]);

                formData.append("files[]", files[index]);
            }

            writeToConsole(`Enviando ${ctdFiles} arquivos...`);

            headers.append('Accept', 'application/json');
            //headers.append("Content-type", "multipart/form-data");

            try {
                let response = await fetch(apiURL, {
                    headers: headers,
                    mode: 'cors', // no-cors, *cors, same-origin
                    body: formData,
                    method: "POST", // *GET, POST, PUT, DELETE, etc.
                    cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                    credentials: 'same-origin', // include, *same-origin, omit
                    redirect: 'follow', // manual, *follow, error
                    referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
                });

                //==>debug
                debug_log(2,"sendFiles response:", response);
                let json = await response.json();

                debug_log(2,"sendFiles json:", json);

                if( "fileList" in json ) {
                    resolve(_copyRecursive(json["fileList"]));
                }
            }catch( err ) {
                debug_log(1,"sendFiles->catch:",err);
                reject();
            }
        }
    });
}

// update the objAtendimento filds with excel values
function setAtendimentoDcp(objAtd1, objAtd2) {
    //==>trace
    trace_log(1,"setAtendimentoDcp:", objAtd1, objAtd2);

    let errorMsg = "";

    if (objAtd2.codAtendimento && objAtd2.codAtendimento.trim().length > 0 && 
        (objAtd1.codAtendimento != objAtd2.codAtendimento )) {
        errorMsg += `codAtendimento:${objAtd2.codAtendimento}=>${objAtd1.codAtendimento}|`;
        objAtd1.codAtendimento = objAtd2.codAtendimento;
    }

    if (objAtd2.data && objAtd2.data.trim().length > 0 && (objAtd1.data != objAtd2.data)) {
        errorMsg += `data:${objAtd2.data}=>${objAtd1.data}|`;
        objAtd1.data = objAtd2.data;
    }

    if (objAtd2.navio && objAtd2.navio.trim().length>0 && (objAtd1.navio != objAtd2.navio) ) {
        errorMsg += `navio:${objAtd2.navio}=>${objAtd1.navio}|`;
        objAtd1.navio = objAtd2.navio;
    }

    if (Number(objAtd2.balanca) > 0 && (Number(objAtd1.balanca) != Number(objAtd2.balanca)) ) {    
        errorMsg += `balanca:${objAtd2.balanca}=>${objAtd1.balanca}`;
        objAtd1.balanca = objAtd2.balanca;
    }

    if (Number(objAtd2.arqueacao) > 0 && (Number(objAtd1.arqueacao) != Number(objAtd2.arqueacao)) ) {
        errorMsg += `arqueacao:${objAtd2.arqueacao}=>${objAtd1.arqueacao}|`;
        objAtd1.arqueacao = objAtd2.arqueacao;
    }

    if (Number(objAtd2.comando_navio) > 0 && (Number(objAtd1.comando_navio) != Number(objAtd2.comando_navio)) ) {
        errorMsg += `comando_navio:${objAtd2.comando_navio}=>${objAtd1.comando_navio}|`;
        objAtd1.comando_navio = objAtd2.comando_navio;
    }

    if (Number(objAtd2.perito_receita) > 0 && (Number(objAtd1.perito_receita) != Number(objAtd2.perito_receita)) ) {
        errorMsg += `perito_receita:${objAtd2.perito_receita}=>${objAtd1.perito_receita}|`;
        objAtd1.perito_receita = objAtd2.perito_receita;
    }

    if (Number(objAtd2.outras_partes1) > 0 && (Number(objAtd1.outras_partes1) != Number(objAtd2.outras_partes1)) ) {
        errorMsg += `outras_partes1:${objAtd2.outras_partes1}=>${objAtd1.outras_partes1}|`;
        objAtd1.outras_partes1 = objAtd2.outras_partes1;
    }

    if (objAtd2.outras_partes1_id.length>0 && objAtd2.outras_partes1_id[0] != '-' && 
        objAtd1.outras_partes1_id != objAtd2.outras_partes1_id ) {
        errorMsg += `outras_partes1_id:${objAtd2.outras_partes1_id}=>${objAtd1.outras_partes1_id}|`;
        objAtd1.outras_partes1_id = objAtd2.outras_partes1_id;
    }

    if (Number(objAtd2.outras_partes2) > 0 && (Number(objAtd1.outras_partes2) != Number(objAtd2.outras_partes2)) ) {
        errorMsg += `outras_partes2:${objAtd2.outras_partes2}=>${objAtd1.outras_partes2}|`;
        objAtd1.outras_partes2 = objAtd2.outras_partes2;
    }

    if (objAtd2.outras_partes2_id.length>0 && objAtd2.outras_partes2_id[0] != '-' && 
        objAtd1.outras_partes2_id != objAtd2.outras_partes2_id ) {
        errorMsg += `outras_partes2_id:${objAtd2.outras_partes2_id}=>${objAtd1.outras_partes2_id}|`;
        objAtd1.outras_partes2_id = objAtd2.outras_partes2_id;
    }

    if (Number(objAtd2.outras_partes3) > 0 && (Number(objAtd1.outras_partes3) != Number(objAtd2.outras_partes3)) ) {
        errorMsg += `outras_partes3:${objAtd2.outras_partes3}=>${objAtd1.outras_partes3}|`;
        objAtd1.outras_partes3 = objAtd2.outras_partes3;
    }
    
    if (objAtd2.outras_partes3_id.length>0 && objAtd2.outras_partes3_id[0] != '-' && 
        objAtd1.outras_partes3_id != objAtd2.outras_partes3_id ) {
        errorMsg += `outras_partes3_id:${objAtd2.outras_partes3_id}=>${objAtd1.outras_partes3_id}|`;
        objAtd1.outras_partes3_id = objAtd2.outras_partes3_id;
    }

    if (parseFloat(objAtd2.excesso).toFixed(3) != parseFloat(objAtd1.excesso).toFixed(3)) {
        errorMsg += `excesso:${objAtd2.excesso}=>${objAtd1.excesso}|`;
        objAtd1.excesso = objAtd2.excesso;
    }

    if (parseFloat(objAtd2.falta).toFixed(3) != parseFloat(objAtd2.falta).toFixed(3)) {
        errorMsg += `falta:${objAtd2.falta}=>${objAtd1.falta}|`;
        objAtd1.falta = objAtd2.falta;
    }

    if (parseFloat(objAtd2.diferenca).toFixed(2) != parseFloat(objAtd1.diferenca).toFixed(2)) {
        errorMsg += `diferenca:${objAtd2.diferenca}=>${objAtd1.diferenca}|`;
        objAtd1.diferenca = objAtd2.diferenca;
    }

    if( objAtd1.produtos.length != objAtd2.produtos.length ) {
        errorMsg += `produtos:${objAtd2.produtos.length}=>${objAtd1.produtos.length}|`;
        objAtd1.produtos = _copyRecursive(objAtd2.produtos);
        objAtd1.lstProdutos = _copyRecursive(objAtd2.produtos);
    } else if ( objAtd1.produtos.filter((item) => {
        if( !objAtd2.produtos.includes(item) ) {
            errorMsg += `produto:${item}|`;
            return true;
        } else {
            return false;
        }
    }).length > 0 ) {
        objAtd1.produtos = _copyRecursive(objAtd2.produtos);
        objAtd1.lstProdutos = _copyRecursive(objAtd2.produtos);
    }
    
    if( objAtd1.terminais.length != objAtd2.terminais.length ) {
        errorMsg += `terminais:${objAtd2.terminais.length}=>${objAtd1.terminais.length}|`;
        objAtd1.terminais = _copyRecursive(objAtd2.terminais);
        objAtd1.lstTerminais = _copyRecursive(objAtd2.terminais);
    } else if (objAtd1.terminais.filter((item) => {
        if( !objAtd2.terminais.includes(item) ) {
            errorMsg += `terminal:${item}|`;
            return true;
        }else {
            return false;
        }
    }).length > 0 ) {
        objAtd1.terminais = _copyRecursive(objAtd2.terminais);
        objAtd1.lstTerminais = _copyRecursive(objAtd2.terminais);
    }

    if( objAtd1.lstPoroes.length != objAtd2.lstPoroes.length ) {
        errorMsg += `lstPoroes:${objAtd2.lstPoroes.length}=>${objAtd1.lstPoroes.length}|`;
        objAtd1.lstPoroes = _copyRecursive(objAtd2.lstPoroes);
    } else if ( objAtd1.lstPoroes.filter( (item) => {
        if( !objAtd2.lstPoroes.includes(item) ) {
            errorMsg += `lstPoroes:${item}|`;
            return true;
        } else {
            return false;
        }
    }).length > 0 ) {
        objAtd1.lstPoroes = _copyRecursive(objAtd2.lstPoroes);
    }

    if( Object.keys(objAtd2.poroes).length != Object.keys(objAtd1.poroes).length ) {
        errorMsg += `poroes:${Object.keys(objAtd2.poroes).length}=>${Object.keys(objAtd1.poroes).length}|`;
        objAtd1.poroes = _copyRecursive(objAtd2.poroes);
    } else { 
        Object.keys(objAtd1.poroes).forEach(poraoId => {
            let filterCount = (Object.keys(objAtd1.poroes[poraoId]).filter((item) => {
                let retval = false;

                if (!(poraoId in objAtd2.poroes) ) {
                    errorMsg += `poroes:${poraoId}|`;
                    return true;
                } else if( !(item in objAtd2.poroes[poraoId]) ) {
                    errorMsg += `poroes:${item}|`;
                    return true;
                } else {
                    switch (item) {
                        case "produto_id":
                            {
                                retval = Number(objAtd1.poroes[poraoId][item]) != Number(objAtd2.poroes[poraoId][item]);
                                break;
                            }
                        case "fatorestiva":
                            {
                                retval = (parseFloat(objAtd1.poroes[poraoId][item]).toFixed(2) != parseFloat(objAtd2.poroes[poraoId][item]).toFixed(2));
                                break;
                            }
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
                        case "terminais":
                            {
                                Object.keys(objAtd1.poroes[poraoId][item]).forEach(termId => {
                                    if( !(termId in objAtd2.poroes[poraoId][item]) ) {
                                        errorMsg += `termId:${termId}|`;
                                    } else if( !("quantidade" in objAtd2.poroes[poraoId][item][termId]) ||
                                        Number(objAtd1.poroes[poraoId][item][termId]["quantidade"]) !=
                                        Number(objAtd2.poroes[poraoId][item][termId]["quantidade"]) ) {
                                        retval = true;
                                        errorMsg += `quantidade:${objAtd2.poroes[poraoId][item][termId]["quantidade"]}=>${objAtd1.poroes[poraoId][item][termId]["quantidade"]}|`;
                                    }              
                                });
                                break;
                            }
                    }

                    if( retval ) errorMsg += `${item}:${objAtd2.poroes[poraoId][item]}=>${objAtd1.poroes[poraoId][item]}|`;
                }

                return retval;
            }));

            if (filterCount.length > 0) {
                objAtd1.poroes = _copyRecursive(objAtd2.poroes);
            }
        });
    }

    if (errorMsg.length > 0) {
        //==>debug
        debug_log(2,"end(setAtendimentoDcp): objAtd1", objAtd1);
    }

    return errorMsg;
}

const errorBits = {
    "part1": 1<<0,
    "lstProdutos": 1<<1,
    "lstTerminais": 1<<2,
    "lstPoroes": 1<<3,
    "poroes": 1<<4,
    "porTerm": 1<<5,
    "files": 1<<6,
};

async function onSaveForm1(evt) {
    //==>trace
    trace_log(1,"onSaveForm1 evt:", evt);

    let id = 0;
    let isUpdate = false;

    if(isReference && ( objAtendimento.cliente.length < 2 || objAtendimento.codAtendimento.length < 5) ) {
        let errMsg = "onSaveForm1:";
        
        if( objAtendimento.cliente.length < 2 ) {
            errMsg += ` objAtendimento.cliente must contain at least 2 chars`;
            objAtendimento.cliente = savedObjAtendimento.cliente;
        }

        if( objAtendimento.codAtendimento < 5 ) {
            errMsg += ` objAtendimento.codAtendimento must contain at least 5 chars`;
            objAtendimento.codAtendimento = savedObjAtendimento.codAtendimento;
        }

        showCurrentAtendimento();

        writeToConsole(errMsg);
        debug_log( 1, errMsg );

    } else {
        let objToSend = deepCopyObject(objAtendimento);
        objToSend["changes"] = 0;

        //==>debug
        debug_log(2,"onSaveForm1->objToSend:", objToSend);

        if( objToSend != null && ("atdId" in objToSend) ) {
            id = Number(objToSend.atdId);
        }

        let files;

        if (id > 0) { // Update
            isUpdate = true;

            objToSend["changes"] = cmpAtendimentoDcp(objToSend, savedObjAtendimento);

            if( isReference == false ) {
                files = document.getElementById("planodecarga").files;
                objToSend["changes"] ^= errorBits.files;
            }

            if( objToSend["changes"] > 0 ) {
                writeToConsole( cmpAtendimentoDcp(objToSend, savedObjAtendimento, true) );
            }

            debug_log(3, `onSaveForm1->update: changes: ${Number(objToSend["changes"])}` );
        } else {
            debug_log(3, `onSaveForm1->add new:` );
        }

        if( isUpdate == false || Number(objToSend["changes"]) > 0 ) {
            const response = await sendJson(objToSend, `/atendimentos/api/atendimentos.php`);

            //==>debug
            debug_log( 2, "onSaveForm1:sendJson:response:", response );

            if( response.error ) {
                let errMsg = `onSaveForm1:sendJson(${isUpdate?"update":"Add New"}):response.error: ${JSON.stringify(response.response)}`;
                writeToConsole(errMsg);
                debug_log( 1, errMsg );
            } else {
                const json = response.response;

                if (json["error"]) {
                    let errMsg = `onSaveForm1:sendJson(${isUpdate?"update":"Add New"}):json["error"]: ${JSON.stringify(json["error"])}`;
                    writeToConsole(errMsg);
                    console.error(errMsg);
                } else {
                    //==>debug
                    debug_log(3, 'onSaveForm1:sendJson(${isUpdate?"update":"Add New"}):json:', json );

                    if (id != json.atdId) {
                        //==>debug
                        debug_log(2,`onSaveForm1: atdId(${id})!=json.atdId(${json.atdId})`);
                        
                        objAtendimento = await loadAtendimento(json.atdId);
                    }

                    atdId = objAtendimento.atdId;
                    id = atdId;
                }
            }
        }

        if( isReference == false ) {
            if( id > 0 && files.length > 0 ) {
                debug_log( 2, "there's files to send! changes:", files.length );

                let lstPlanos = await sendFiles(files, `/atendimentos/api/planos.php?id=${id}`);            
                debug_log( 2, "onSaveForm1:lstPlanos", lstPlanos );

                if( lstPlanos && lstPlanos.length > 0 ) {
                    objAtendimento.lstPlanos = [...lstPlanos];
                    //fillDeleteFileList();
                }
            }
        }

        debug_log(3,"onSaveForm1->showCurrentAtendimento:");
        showCurrentAtendimento();

        if( isUpdate )
            writeToConsole(`Updated (${atdId})!`, 5000);
        else
            writeToConsole(`Saved New atendimento (${atdId})!`, 5000);    

    }
}

function cmpAtendimentoDcp(objAtd1, objAtd2, errorStr=false) {
    //==>trace
    trace_log(1,"cmpAtendimentoDcp");
    let errorMsg = "";
    let retCmp = 0;

    //==>debug
    debug_log(3,"cmpAtendimentoDcp:", objAtd1, objAtd2);

    if ( (objAtd1 && "atdId" in objAtd1 ) && (objAtd2 && "atdId" in objAtd2) ) {

        if (objAtd1.link != objAtd2.link) {
            errorMsg += `link:${objAtd2.link}=>${objAtd1.link}|`;
            retCmp |= errorBits.part1;
        }

        if (objAtd1.cliente != objAtd2.cliente) {
            errorMsg += `cliente:${objAtd2.cliente}=>${objAtd1.cliente}|`;
            retCmp |= errorBits.part1;
        }

        if (Number(objAtd1.atdId) != Number(objAtd2.atdId)) {
            errorMsg += `atdId:${objAtd2.atdId}=>${objAtd1.atdId}|`;
            retCmp |= errorBits.part1;
        }

        if (objAtd1.codAtendimento.trim() != objAtd2.codAtendimento.trim()) {
            errorMsg += `codAtendimento:${objAtd2.codAtendimento}=>${objAtd1.codAtendimento}|`;
            retCmp |= errorBits.part1;
        }

        if (objAtd1.data != objAtd2.data) {
            errorMsg += `data:${objAtd2.data}=>${objAtd1.data}|`;
            retCmp |= errorBits.part1;
        }

        if (objAtd1.navio != objAtd2.navio) {
            errorMsg += `navio:${objAtd2.navio}=>${objAtd1.navio}|`;
            retCmp |= errorBits.part1;
        }

        if (parseFloat(objAtd1.balanca).toFixed(3) != parseFloat(objAtd2.balanca).toFixed(3)) {
            errorMsg += `balanca:${objAtd2.balanca}=>${objAtd1.balanca}|`;
            retCmp |= errorBits.part1;
        }

        if (parseFloat(objAtd1.arqueacao).toFixed(3) != parseFloat(objAtd2.arqueacao).toFixed(3)) {
            errorMsg += `arqueacao:${objAtd2.arqueacao}=>${objAtd1.arqueacao}|`;
            retCmp |= errorBits.part1;
        }

        if (parseFloat(objAtd1.comando_navio).toFixed(3) != parseFloat(objAtd2.comando_navio).toFixed(3)) {
            errorMsg += `comando_navio:${objAtd2.comando_navio}=>${objAtd1.comando_navio}|`;
            retCmp |= errorBits.part1;
        }

        if (parseFloat(objAtd1.perito_receita).toFixed(3) != parseFloat(objAtd2.perito_receita).toFixed(3)) {
            errorMsg += `perito_receita:${objAtd2.perito_receita}=>${objAtd1.perito_receita}|`;
            retCmp |= errorBits.part1;
        }

        if (parseFloat(objAtd1.outras_partes1).toFixed(3) != parseFloat(objAtd2.outras_partes1).toFixed(3)) {
            errorMsg += `outras_partes1:${objAtd2.outras_partes1}=>${objAtd1.outras_partes1}|`;
            retCmp |= errorBits.part1;
        }

        if (objAtd1.outras_partes1_id != objAtd2.outras_partes1_id) {
            errorMsg += `outras_partes1_id:${objAtd2.outras_partes1_id}=>${objAtd1.outras_partes1_id}|`;
            retCmp |= errorBits.part1;
        }

        if (parseFloat(objAtd1.outras_partes2).toFixed(3) != parseFloat(objAtd2.outras_partes2).toFixed(3)) {
            errorMsg += `outras_partes2:${objAtd2.outras_partes2}=>${objAtd1.outras_partes2}|`;
            retCmp |= errorBits.part1;
        }

        if (objAtd1.outras_partes2_id != objAtd2.outras_partes2_id) {
            errorMsg += `outras_partes2_id:${objAtd2.outras_partes2_id}=>${objAtd1.outras_partes2_id}|`;
            retCmp |= errorBits.part1;
        }
        
        if (parseFloat(objAtd1.outras_partes3).toFixed(3) != parseFloat(objAtd2.outras_partes3).toFixed(3)) {
            errorMsg += `outras_partes3:${objAtd2.outras_partes3}=>${objAtd1.outras_partes3}|`;
            retCmp |= errorBits.part1;
        }

        if (objAtd1.outras_partes3_id != objAtd2.outras_partes3_id) {
            errorMsg += `outras_partes3_id:${objAtd2.outras_partes3_id}=>${objAtd1.outras_partes3_id}|`;
            retCmp |= errorBits.part1;
        }

        if (parseFloat(objAtd1.excesso).toFixed(3) != parseFloat(objAtd2.excesso).toFixed(3)) {
            errorMsg += `excesso:${objAtd2.excesso}=>${objAtd1.excesso}|`;
            retCmp |= errorBits.part1;
        }

        if (parseFloat(objAtd1.falta).toFixed(3) != parseFloat(objAtd2.falta).toFixed(3)) {
            errorMsg += `falta:${objAtd2.falta}=>${objAtd1.falta}|`;
            retCmp |= errorBits.part1;
        }

        if (parseFloat(objAtd1.diferenca).toFixed(2) != parseFloat(objAtd2.diferenca).toFixed(2)) {
            errorMsg += `diferenca:${objAtd2.diferenca}=>${objAtd1.diferenca}|`;
            retCmp |= errorBits.part1;
        }

        if( objAtd1.lstProdutos.length != objAtd2.lstProdutos.length ) {
            errorMsg += `lstProdutos:${objAtd2.lstProdutos.length}=>${objAtd1.lstProdutos.length}|`;
            retCmp |= errorBits.lstProdutos;
        } else if ( objAtd1.lstProdutos.filter((item) => {
            if( !objAtd2.lstProdutos.includes(item) ) {
                errorMsg += `lstProdutos:${item}|`;
                return true;
            } else {
                return false;
            }
        }).length > 0 ) {
            retCmp |= errorBits.lstProdutos;
        }

        if( objAtd1.lstTerminais.length != objAtd2.lstTerminais.length ) {
            errorMsg += `lstTerminais:${objAtd2.lstTerminais.length}=>${objAtd1.lstTerminais.length}|`;
            retCmp |= errorBits.lstTerminais;
        } else if (objAtd1.lstTerminais.filter((item) => {
            if( !objAtd2.lstTerminais.includes(item) ) {
                errorMsg += `lstTerminais:${item}|`;
                return true;
            }else {
                return false;
            }
        }).length > 0 ) {
            retCmp |= errorBits.lstTerminais;
        }

        if( objAtd1.lstPoroes.length != objAtd2.lstPoroes.length ) {
            errorMsg += `lstPoroes:${objAtd2.lstPoroes.length}=>${objAtd1.lstPoroes.length}|`;
            retCmp |= errorBits.lstPoroes;
        } else if ( objAtd1.lstPoroes.filter( (item) => {
            if( !objAtd2.lstPoroes.includes(item) ) {
                errorMsg += `lstPoroes:${item}|`;
                return true;
            } else {
                return false;
            }
        }).length > 0 ) {
            retCmp |= errorBits.lstPoroes;
        }

        if( Object.keys(objAtd1.poroes).length != Object.keys(objAtd2.poroes).length ) {
            errorMsg += `poroes:${Object.keys(objAtd2.poroes).length}=>${Object.keys(objAtd1.poroes).length}|`;
            retCmp |= errorBits.poroes;
        } else { 
            Object.keys(objAtd1.poroes).forEach(poraoId => {
                let filterCount = (Object.keys(objAtd1.poroes[poraoId]).filter((item) => {
                    let retval = false;

                    if (!(poraoId in objAtd2.poroes) ) {
                        errorMsg += `poroes:${poraoId}|`;
                        return true;
                    } else if( !(item in objAtd2.poroes[poraoId]) ) {
                        errorMsg += `poroes:${item}|`;
                        return true;
                    } else {
                        switch (item) {
                            case "produto_id":
                                {
                                    retval = Number(objAtd1.poroes[poraoId][item]) != Number(objAtd2.poroes[poraoId][item]);
                                    break;
                                }
                            case "fatorestiva":
                                {
                                    retval = (parseFloat(objAtd1.poroes[poraoId][item]).toFixed(2) != parseFloat(objAtd2.poroes[poraoId][item]).toFixed(2));
                                    break;
                                }
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
                            case "terminais":
                                {
                                    Object.keys(objAtd1.poroes[poraoId][item]).forEach(termId => {
                                        if( !(termId in objAtd2.poroes[poraoId][item]) ) {
                                            errorMsg += `termId:${termId}|`;
                                        } else if( !("quantidade" in objAtd2.poroes[poraoId][item][termId]) ||
                                            Number(objAtd1.poroes[poraoId][item][termId]["quantidade"]) !=
                                            Number(objAtd2.poroes[poraoId][item][termId]["quantidade"]) ) {
                                            retval = true;
                                            retCmp |= errorBits.porTerm;
                                            errorMsg += `quantidade:${objAtd2.poroes[poraoId][item][termId]["quantidade"]}=>${objAtd1.poroes[poraoId][item][termId]["quantidade"]}|`;
                                        }              
                                    });
                                    break;
                                }
                        }

                        if( retval ) errorMsg += `${item}:${objAtd2.poroes[poraoId][item]}=>${objAtd1.poroes[poraoId][item]}|`;
                    }

                    return retval;
                }));

                if (filterCount.length > 0) {
                    retCmp |= errorBits.poroes;
                }
            });
        }
    }

    let planodecarga = document.getElementById("planodecarga");

    if( planodecarga && planodecarga.files.length > 0 ) {
        errorMsg += `files:${planodecarga.files.length}|`;
        retCmp |= errorBits.files;
    }

    //==>debug
    debug_log(3, `retCmp:${retCmp}:${retCmp.toString(2).padStart(8,'0')}:${errorMsg}`);

    if( errorStr )
        return errorMsg;
    else 
        return retCmp;
}

function updateJsonAtd(objAtd) {
    //==>trace
    trace_log(1,"updateJsonAtd:", objAtd);

    objAtd.lstProdutos = [];
    objAtd.lstTerminais = [];
    objAtd.lstPoroes = [];

    //==>debug
    debug_log(3,'objAtd ini:', objAtd );

    if ("produtos" in objAtd) {
        objAtd.lstProdutos = _copyRecursive(objAtd.produtos);
    }

    if ("terminais" in objAtd)
        objAtd.lstTerminais = _copyRecursive(objAtd.terminais);

    if ("poroes" in objAtd) {
        Object.keys(objAtd.poroes).forEach(poraoId => {
            objAtd.lstPoroes.push(poraoId);
        });
    } else {
        objAtd.poroes = {};
    }

    //==>debug
    debug_log(3,'objAtd fin:', objAtd );
}

async function loadAtendimento(id, idType="id", mode="json") { // idType=id,navio,codatd
    //==>trace
    trace_log(1,"loadAtendimento:", id);
    let urlToCall = `/atendimentos/api/atendimentos.php?mode=${mode}`;

    //==>debug
    debug_log(2,`loadAtendimento id:${id}`);
    debug_log(2,`loadAtendimento idType:${idType}`);

    if( `${id}` && `${id}`.length > 0 ) {
        debug_log(2,"loadAtendimento: id && id.length > 0");

        if( idType === "id" ) {
            debug_log(2,'loadAtendimento: idType === "id"');
            urlToCall += `&id=${id}`;
        } else if( idType === "codatd" ) {
            debug_log(2,'loadAtendimento: idType === "codatd"');
            urlToCall += `&codatd=${id}`;
        } else if( idType === "navio" ) {
            debug_log(2,'loadAtendimento: idType === "navio"');
            urlToCall += `&navio=${id}`;
        }
        
        //==>debug
        debug_log(2,`loadAtendimento urlToCall to call:${urlToCall}`);
        
        return new Promise( (resolve, reject) => {
            //==>debug
            debug_log(2,`loadAtendimento fetch call:${urlToCall}`);
            
            let headers = new Headers();
            headers.append('Accept', 'application/json');
            
            fetch( urlToCall, {
                mode: 'cors', // no-cors, *cors, same-origin
                method: "GET", // *GET, POST, PUT, DELETE, etc.
                headers: headers,
                cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                credentials: 'same-origin', // include, *same-origin, omit
                redirect: 'follow', // manual, *follow, error
                referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            })
            .then( async (response) => {
                debug_log(3, 'resonse:', response );
                return response.json();
            })
            .then(json => {
                //==>debug
                debug_log(3,'loadAtendimento json:', json);
                
                if (json.error && json.error != "ok" ) {
                    //writeToConsole(`loadAtendimento error: ${JSON.stringify(json.error)}`, 10);
                    resolve(null);
                } else {
                    //objAtendimento = JSON.parse(JSON.stringify(json[atdId]));
                    
                    let objAtd = {};
                    
                    Object.keys(json).forEach( key => {
                        objAtd = {...json[key] }; 
                    });
                    
                    if( objAtd && objAtd.cliente && objAtd.cliente.length > 0 ) {
                        isReference = true;
                    } else {
                        isReference = false;
                    }
                    
                    updateJsonAtd(objAtd);
                    resolve(_copyRecursive(objAtd));
                }
            })
            .catch((err) => {
                let error = `catch: fetch objAtendimento: ${err.message}`;
                writeToConsole(error);
                reject(error);
            });
        });
    } else {
        debug_log(2,`loadAtendimento: !(${id} && "${id}".length > 0 )` );
    }
}

function createNewAtendimento() {
    var objAtd = {
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
        "link": "",
        "cliente": "",
        "lstProdutos": [],
        "lstTerminais": [],
        "lstPoroes": [],
        "produtos": [],
        "terminais": [],
        "poroes": {}
    };

    return objAtd;
}

function showCurrentAtendimento() {
    //==>trace
    trace_log(1,"showCurrentAtendimento:" );
    debug_log(2,"showCurrentAtendimento->objAtendimento:", objAtendimento );

    if( !objAtendimento || ("atdId" in objAtendimento)==false ) {
        objAtendimento = _copyRecursive(createNewAtendimento());
    }

    atdId = objAtendimento.atdId;
    savedObjAtendimento = deepCopyObject(objAtendimento);

    //==>debug
    debug_log(2,'showCurrentAtendimento->objAtendimento:', objAtendimento);
    debug_log(2,'onLoadBodyshowCurrentAtendimento->savedObjAtendimento:', savedObjAtendimento);

    crateMainApp();
    fillGroup1();

    if( isReference == false ) {
        fillDeleteFileList();
        fillDcpTable();
    }
}

var modalWindow = document.getElementById("modal-window");
var spanClose = document.getElementById("modal-close");
var iframe = document.getElementById("modal-content");

if (modalWindow == null) {
    modalWindow = document.createElement("DIV");
    modalWindow.setAttribute("id", "modal-window");
    modalWindow.setAttribute("class", "modal2");

    let modalContent = document.createElement("DIV");
    modalContent.setAttribute("class", "modal2-content");

    spanClose = document.createElement("SPAN");
    spanClose.setAttribute("class", "close");
    spanClose.setAttribute("id", "modal-close");
    spanClose.innerHTML = "&times;";

    modalContent.appendChild(spanClose);

    let spanModalContent = document.createElement("SPAN");
    spanModalContent.setAttribute("class", "modal2-content");

    iframe = document.createElement("IFRAME");
    iframe.setAttribute("id", "modal-content");
    iframe.setAttribute("class", "modal2-content");
    iframe.setAttribute("src", "");
    spanModalContent.appendChild(iframe);
    modalContent.appendChild(spanModalContent);
    modalWindow.appendChild(modalContent);
    document.body.appendChild(modalWindow);
}

spanClose.onclick = function () {
    document.getElementById("modal-window").style.display = "none";
    location.reload();
}

window.onclick = function (event) {
    modalWindow = document.getElementById("modal-window");
    if (event.target == modalWindow) {
        modalWindow.style.display = "none";
    }
}

function openModalWindow(filename = "", url = "") {
    if (filename != "" || url != "") {
        if (filename != "")
            iframe.setAttribute("src", filename);
        else {
            let xhr = new XMLHttpRequest();
            let file2load = document.getElementById("filetoload").files[0];

            let formData = new FormData();
            formData.append("file", file2load);

            xhr.open("POST", url); //Synchronous requests
            
            xhr.onload = () => console.log(xhr.response);

            // track upload progress
            xhr.upload.onprogress = function (event) {
                console.log(`Uploaded ${event.loaded} of ${event.total}`);
            };

            // track completion: both successful or not
            xhr.onloadend = function () {
                if (xhr.status == 200) {
                    iframe.setAttribute("src", "data:text/html," + escape(xhr.response));
                    //iframe.setAttribute("src", "data:application/pdf," + escape(xhr.response));
                    console.log("success");
                } else {
                    console.log("error " + this.status);
                }
            };

            xhr.send(formData);
        }

        modalWindow.style.display = "block";
    }
}

function setCookie(name, value, seconds) {
    if (typeof (seconds) != 'undefined') {
        var date = new Date();
        date.setTime(date.getTime() + (seconds * 1000));
        var expires = "; expires=" + date.toGMTString();
    } else {
        var expires = "";
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

function getCookie(name) {
    name = name + "=";
    var carray = document.cookie.split(';');

    for (var i = 0; i < carray.length; i++) {
        var c = carray[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }

    return null;
}

function deleteCookie(name) {
    setCookie(name, "", -1);
}

(async function onLoadBody() {
    //==>trace
    trace_log(1,"onLoadBody:" );

    numeral.locale(szCurrentLocale);
    
    writeToConsole();

    const currentUrl = window.location.href;

    if( currentUrl.includes("atendimentos/link") ) {
        readOnly = true;
        isLink = true;
    } else
        readOnly = getParameterByName('edit') ? true : false;

    debug_log(2,"onLoadBody: readOnly:", readOnly);
    var objAtd = null;

    atdId = getParameterByName('id') | getParameterByName('atdid');
    debug_log(2,"onLoadBody: id:", atdId);

    let tag = getParameterByName('tag');

    if( (tag && tag.length > 0) ) {
        debug_log(2,"onLoadBody: tag:", tag);
        objAtd = await loadAtendimento(tag, "codatd");
    } else {
        if (atdId && Number(atdId) > 0) {
            //==>debug
            debug_log(2,"onLoadBody: atdId:", atdId);

            objAtd = await loadAtendimento(atdId);
        }
    }

    isReference = getParameterByName('new')=='ref'? true : false;

    if( !objAtd || Number(objAtd.atdId) == 0 ) {
        objAtendimento = createNewAtendimento();
    } else {
        objAtendimento = deepCopyObject(objAtd);
        atdId = objAtd.atdId;
        isReference = (objAtd.cliente.length > 0)?true:false;
    }

    debug_log(2,"onLoadBody: isReference:", isReference);

    if( isReference == false ) {
        objTerminais = await loadTerminais();
        debug_log(1, "onLoadBody->loadTerminais:", objTerminais);

        objProdutos = await loadProdutos();
        debug_log(1, "onLoadBody->loadProdutos:", objProdutos);    
    }

    showCurrentAtendimento();
})();
