import './style.css';

import { numeral,szCurrentLocale,getParameterByName,writeToConsole, 
    trace_log,debug_log,_copyRecursive} 
    from '../lib/helper_functions.js';

var objProdutos = {};
var savedObjProdutos = {};

var produto_id = 0;
var changes = 0;

const changeBits = {
  "new": 0xffff,
  "nome": 1<<0,
  "descricao": 1<<1,
  "tags": 1<<2,
};

function onChangeForm1(evt) {
  let fldName = evt.target.name.toUpperCase();
  let fldValue = evt.target.value;

  let pattern = /^([A-Z]+)_?(\d*)_?(\d*)/;
  let fldBaseName = fldName.match(pattern);

  writeToConsole();

  //==>debug
  debug_log(2, "onChangeForm1 => fldName:", fldName, "fldBaseName:", fldBaseName, "fldValue:", fldValue, "evt.target:", evt.target);

  if( fldBaseName.length > 1 ) {
    //==>debug
    debug_log(3,"onChangeForm1->fldBaseName[1]:", fldBaseName[1] );

    switch (fldBaseName[1]) {
        case "NOME": 
        {
          objProdutos.nome = fldValue;
          evt.target.value = objProdutos.nome;

          let a = objProdutos.nome;
          let b = savedObjProdutos.nome;

          if( ((!b || b.length == 0) &&
            (a.trim().length > 0 )) || 
            ((b && b.length > 0) && 
            (a.toLowerCase().trim() != b.toLowerCase().trim()))) {
            changes |= changeBits.nome;
          } else {
            changes &= ~changeBits.nome;
          }

          break;
        }
        case "DESCRICAO": 
        {
          objProdutos.descricao = fldValue;
          evt.target.value = objProdutos.descricao;

          let a = objProdutos.descricao;
          let b = savedObjProdutos.descricao;

          if( ((!b || b.length == 0) &&
            (a.trim().length > 0 )) || 
            ((b && b.length > 0) && 
            (a.toLowerCase().trim() != b.toLowerCase().trim()))) {
            changes |= changeBits.descricao;
          } else {
            changes &= ~changeBits.descricao;
          }

          break;
        }
        case "TAGS": 
        {
          objProdutos.tags = fldValue;
          evt.target.value = objProdutos.tags;

          let a = objProdutos.tags;
          let b = savedObjProdutos.tags;

          if( ((!b || b.length == 0) &&
            (a.trim().length > 0 )) || 
            ((b && b.length > 0) && 
            (a.toLowerCase().trim() != b.toLowerCase().trim()))) {
            changes |= changeBits.tags;
          } else {
            changes &= ~changeBits.tags;
          }

          break;
        }
      }

    if( changes > 0 ) {
      debug_log( 1, "changes: ", changes.toString(2) );
      disableButton(false);
    } else {
      disableButton(true);
    }
  }
}

async function sendJson(json, apiURL) {
  //==>trace
  trace_log(1,"sendJson:", json, apiURL );

  let id = (json && json.produto_id) ? json.produto_id : 0;
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

async function onSaveForm1(evt) {
  //==>trace
  trace_log(1,"onSaveForm1 evt:", evt);

  let objChanges = {};
  let id = (objProdutos && objProdutos.produto_id) ? objProdutos.produto_id : 0;
  let isUpdate = false;

  if( id == 0 ) 
    changes = changeBits.new;

  if( changes > 0 ) {
      //==>debug
    debug_log(1, "onSaveForm1: id: ", id, "changes: ", changes.toString(2) );

    objChanges["produto_id"] = id;

    if( (changes & changeBits.nome) > 0  ) {
      objChanges["nome"] = objProdutos.nome;
    }
   
    if( (changes & changeBits.descricao) > 0  ) {
      objChanges["descricao"] = objProdutos.descricao;
    }

    if( (changes & changeBits.tags) > 0  ) {
      objChanges["tags"] = objProdutos.tags;
    }

    //==>debug
    debug_log(2,"onSaveForm1->objChanges:", objChanges);

    if (id > 0) { // Update
      isUpdate = true;

      debug_log(3, `onSaveForm1->update: changes: ${changes}` );
    } else {
      debug_log(3, `onSaveForm1->add new:` );
    }

    const response = await sendJson(objChanges, `/atendimentos/api/produtos.php`);

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

        if (id != json.produto_id) {
          //==>debug
          debug_log(2,`onSaveForm1: id(${id})!=json.produto_id(${json.produto_id})`);
          
          objProdutos = await loadProduto(json.produto_id);
        }

        produto_id = objProdutos.produto_id;
        id = produto_id;

        savedObjProdutos = JSON.parse(JSON.stringify(objProdutos));
        disableButton(true);
        changes = 0;
    
        crateMainApp();
      
        if( isUpdate )
          writeToConsole(`Updated (${produto_id})!`, 5000);
        else
          writeToConsole(`Saved New produto (${produto_id})!`, 5000);    
      }
    }
  }
}

function disableButton(status = true) {
  //==>trace
  trace_log(1, "disableButton:", status);

  let bt = document.getElementById("salvar");
  
  if( bt )
      bt.disabled = (produto_id==0)?false:status;
}

// Main App
function crateMainApp() {
  const mainApp = document.getElementById("MainApp");
  mainApp.innerText = "";

  const frmForm1 = document.createElement("FORM");
  frmForm1.id = "form1";
  frmForm1.enctype = "multipart/form-data";

  frmForm1.onchange = function(evt) {
      return onChangeForm1(evt);
  };

  // produto_id
  let divNode; 
  let inputNode;
  let label;
  let pNode;
  
  let fieldSet = document.createElement("FIELDSET");
  let legend = document.createElement("LEGEND");
  legend.innerText = `Produto id: ${produto_id&&produto_id>0?produto_id:'New'}`;
  fieldSet.appendChild(legend);

  // nome
  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="email">Nome:</label>`
  pNode.appendChild(label);
  inputNode = document.createElement("INPUT");
  inputNode.name = "nome";
  inputNode.type = "text";
  inputNode.id = "nome";
  inputNode.size = "30";
  inputNode.maxlength = "30";
  inputNode.value = (objProdutos && objProdutos.nome)?objProdutos.nome:"";
  pNode.appendChild(inputNode);
  fieldSet.appendChild(pNode);

  //Descricao
  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="descricao">Descrição:</label>`
  pNode.appendChild(label);
  inputNode = document.createElement("INPUT");
  inputNode.name = "descricao";
  inputNode.type = "text";
  inputNode.id = "descricao";
  inputNode.size = "50";
  inputNode.maxlength = "50";
  inputNode.value = (objProdutos && objProdutos.descricao)?objProdutos.descricao:"";
  pNode.appendChild(inputNode);
  fieldSet.appendChild(pNode);

  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="tags">Tags:</label>`
  pNode.appendChild(label);
  inputNode = document.createElement("INPUT");
  inputNode.name = "tags";
  inputNode.type = "text";
  inputNode.id = "tags";
  inputNode.size = "150";
  inputNode.maxlength = "250";
  inputNode.value = (objProdutos && objProdutos.tags)?objProdutos.tags:"";
  pNode.appendChild(inputNode);
  fieldSet.appendChild(pNode);

  // button
  divNode = document.createElement("DIV");
  inputNode = document.createElement("INPUT");
  inputNode.type = "button";
  inputNode.id = "salvar";

  inputNode.disabled = (produto_id&&produto_id>0)?true:false;

  if (produto_id && Number(produto_id) > 0)
    inputNode.value = "Save";
  else
    inputNode.value = "Add New";

  inputNode.onclick = function(evt) {
    onSaveForm1(evt);
  };

  divNode.appendChild(inputNode);

  fieldSet.appendChild(divNode);

  frmForm1.appendChild(fieldSet);

  mainApp.appendChild(frmForm1);
}

async function loadProduto(produto_id) {

  return new Promise((resolve, reject) => {
    
    //==>debug
    console.log("loadProduto fetch call:", `/atendimentos/api/produtos.php?id=${produto_id}&mode=json`);

    let headers = new Headers();
    headers.append('Accept', 'application/json');

    fetch(`/atendimentos/api/produtos.php?id=${produto_id}&mode=json`, {
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
            console.log('loadProduto json:', json);

            if (json.error) {
                writeToConsole(`loadProduto error: ${JSON.stringify(json.error)}`);
                reject(json.error);
            }

            objProdutos = JSON.parse(JSON.stringify(json[produto_id]));
            objProdutos["produto_id"] = produto_id;

            savedObjProdutos = JSON.parse(JSON.stringify(objProdutos));

            //==>debug
            console.log('loadProduto (response) objProdutos:', objProdutos);

            crateMainApp();

            resolve(objProdutos);
        })
        .catch((err) => {
            let error = `catch: fetch objProdutos: ${err.message}`;
            writeToConsole(error);
            reject(error);
        });
    });
}

(async function onLoadBody() {
  //==>trace
  trace_log(1,"onLoadBody:" );

  numeral.locale(szCurrentLocale);
  
  writeToConsole();
  produto_id = getParameterByName('produto_id');

  if( Number(produto_id) > 0) {
    objProdutos = await loadProduto(produto_id);
  }

  //==>debug
  debug_log(2, "onLoadBody: produto_id:", produto_id);

  crateMainApp();
})();
