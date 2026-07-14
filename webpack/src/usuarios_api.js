import './style.css';

import { numeral,szCurrentLocale,getParameterByName,writeToConsole, 
    trace_log,debug_log,_copyRecursive} 
    from '../lib/helper_functions.js';

var objCadastro = {};
var savedObjCadastro = {};
var cadastro_id = 0;
var changes = 0;

const changeBits = {
  "new": 0xffff,
  "nome": 1<<0,
  "email": 1<<1,
  "nivel": 1<<2,
  "ativo": 1<<3,
  "redefineSenha": 1<<4,
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
          objCadastro.nome = fldValue;
          evt.target.value = objCadastro.nome;

          let a = objCadastro.nome;
          let b = savedObjCadastro.nome;

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
        case "EMAIL":
        {
          objCadastro.email = fldValue;
          evt.target.value = objCadastro.email;

          let a = objCadastro.email;
          let b = savedObjCadastro.email;

          if( ((!b || b.length == 0) &&
            (a.trim().length > 0 )) || 
            ((b && b.length > 0) && 
            (a.toLowerCase().trim() != b.toLowerCase().trim()))) {
            changes |= changeBits.email;
          } else {
            changes &= ~changeBits.email;
          }

          break;
        }
        case "NIVEL":
        {
          objCadastro.nivel = fldValue;
          evt.target.value = objCadastro.nivel;

          let a = objCadastro.nivel;
          let b = savedObjCadastro.nivel;

          if( ((!b || b.length == 0) &&
            (a.length > 0 )) || 
            ((b && b.length > 0) && 
            (a != b))) {
            changes |= changeBits.nivel;
          } else {
            changes &= ~changeBits.nivel;
          }

          break;
        }
        case "ATIVO":
        {
          objCadastro.ativo = evt.target.checked?"S":"N";
          evt.target.value = objCadastro.ativo;
          
          let a = objCadastro.ativo;
          let b = savedObjCadastro.ativo;

          if( ((!b || b.length == 0) &&
            (a.trim().length > 0 )) || 
            ((b && b.length > 0) && 
            (a.toLowerCase().trim() != b.toLowerCase().trim()))) {
            changes |= changeBits.ativo;
          } else {
            changes &= ~changeBits.ativo;
          }

          break;
        }
        case "REDEFINESENHA": 
        {
          objCadastro.redefineSenha = fldValue;
          evt.target.value = objCadastro.redefineSenha;

          let a = objCadastro.redefineSenha;
          let b = savedObjCadastro.redefineSenha;

          if( ((!b || b.length == 0) &&
            (a.trim().length > 0 )) || 
            ((b && b.length > 0) && 
            (a.toLowerCase().trim() != b.toLowerCase().trim()))) {
            changes |= changeBits.redefineSenha;
          } else {
            changes &= ~changeBits.redefineSenha;
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

  let id = (json && json.cadastro_id) ? json.cadastro_id : 0;
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
  let id = (objCadastro && objCadastro.cadastro_id) ? objCadastro.cadastro_id : 0;
  let isUpdate = false;

  if( id == 0 ) 
    changes = changeBits.new;

  if( changes > 0 ) {
      //==>debug
    debug_log(1, "onSaveForm1: id: ", id, "changes: ", changes.toString(2) );

    objChanges["cadastro_id"] = id;

    if( (changes & changeBits.nome) > 0  ) {
      objChanges["nome"] = objCadastro.nome;
    }
   
    if( (changes & changeBits.email) > 0  ) {
      objChanges["email"] = objCadastro.email;
    }

    if( (changes & changeBits.nivel) > 0  ) {
      objChanges["nivel"] = objCadastro.nivel;
    }
    
    if( (changes & changeBits.ativo) > 0  ) {
      objChanges["ativo"] = objCadastro.ativo;
    }
    
    if( (changes & changeBits.redefineSenha) > 0  ) {
      objChanges["redefineSenha"] = objCadastro.redefineSenha;
    }
    
    //==>debug
    debug_log(2,"onSaveForm1->objChanges:", objChanges);

    if (id > 0) { // Update
      isUpdate = true;

      debug_log(3, `onSaveForm1->update: changes: ${changes}` );
    } else {
      debug_log(3, `onSaveForm1->add new:` );
    }

    const response = await sendJson(objChanges, `/atendimentos/api/usuarios.php`);

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

        if (id != json.cadastro_id) {
          //==>debug
          debug_log(2,`onSaveForm1: id(${id})!=json.cadastro_id(${json.cadastro_id})`);
          
          objCadastro = await loadCadastro(json.cadastro_id);
        }

        cadastro_id = objCadastro.cadastro_id;
        id = cadastro_id;

        savedObjCadastro = JSON.parse(JSON.stringify(objCadastro));
        disableButton(true);
        changes = 0;
    
        crateMainApp();
      
        if( isUpdate )
          writeToConsole(`Updated (${cadastro_id})!`, 5000);
        else
          writeToConsole(`Saved New atendimento (${cadastro_id})!`, 5000);    
              
      }
    }

  }
}


function disableButton(status = true) {
  //==>trace
  trace_log(1, "disableButton:", status);

  let bt = document.getElementById("salvar");
  
  if( bt )
      bt.disabled = (cadastro_id==0)?false:status;
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

  // cadastro_id
  let divNode; 
  let inputNode;
  let label;
  let pNode;
  
  let fieldSet = document.createElement("FIELDSET");
  let legend = document.createElement("LEGEND");
  legend.innerText = `Usuário id: ${cadastro_id&&cadastro_id>0?cadastro_id:'New'}`;
  fieldSet.appendChild(legend);

  // nome
  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="nome">Nome:</label>`
  pNode.appendChild(label);
  inputNode = document.createElement("INPUT");
  inputNode.name = "nome";
  inputNode.type = "text";
  inputNode.id = "nome";
  inputNode.size = "50";
  inputNode.maxlength = "100";
  inputNode.value = (objCadastro && objCadastro.nome)?objCadastro.nome:"";
  pNode.appendChild(inputNode);
  fieldSet.appendChild(pNode);

  // email
  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="email">e-mail:</label>`
  pNode.appendChild(label);
  inputNode = document.createElement("INPUT");
  inputNode.name = "email";
  inputNode.type = "text";
  inputNode.id = "email";
  inputNode.size = "50";
  inputNode.maxlength = "100";
  inputNode.value = (objCadastro && objCadastro.email)?objCadastro.email:"";
  pNode.appendChild(inputNode);
  fieldSet.appendChild(pNode);

  // nivel
  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="nivel">Nível:</label>`
  pNode.appendChild(label);

  inputNode = document.createElement('SELECT');
  inputNode.name = 'nivel';

  let optNode;
  //["CLIENTE","FUNCIONARIO","ADMINISTRADOR"]

  optNode = document.createElement("OPTION");
  optNode.value = '0';
  optNode.innerText = 'Cliente';

  if( !objCadastro.nivel )
    objCadastro.nivel = '0';

  if( objCadastro.nivel == '0' )
    optNode.selected = true;

  inputNode.appendChild(optNode);

  optNode = document.createElement("OPTION");
  optNode.value = '1';
  optNode.innerText = 'Funcionario';

  if( objCadastro.nivel == '1' )
    optNode.selected = true;

  inputNode.appendChild(optNode);
  
  optNode = document.createElement("OPTION");
  optNode.value = '2';
  optNode.innerText = 'Administrador';

  if( objCadastro.nivel == '2' )
    optNode.selected = true;

  inputNode.appendChild(optNode);

  pNode.appendChild(inputNode);
  fieldSet.appendChild(pNode);

  // Ativo
  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="ativo">Login Ativo</label>`
  pNode.appendChild(label);
  inputNode = document.createElement("INPUT");
  inputNode.name = "ativo";
  inputNode.type = "checkbox";
  inputNode.id = "ativo";

  if( !objCadastro.ativo || objCadastro.ativo.length == 0 )
    objCadastro.ativo = 'N';

  inputNode.value = objCadastro.ativo;

  if( inputNode.value == 'S' )
    inputNode.checked = true;
  else
    inputNode.checked = false;

  pNode.appendChild(inputNode);
  fieldSet.appendChild(pNode);

  // redefineSenha
  pNode = document.createElement("P");
  label = document.createElement("LABEL");
  label.innerHTML = `<label for="redefineSenha">Nova Senha:</label>`
  pNode.appendChild(label);
  inputNode = document.createElement("INPUT");
  inputNode.name = "redefineSenha";
  inputNode.type = "text";
  inputNode.id = "redefineSenha";
  inputNode.size = "30";
  inputNode.maxlength = "30";

  if( !objCadastro.redefineSenha || objCadastro.redefineSenha.length == 0 )
    objCadastro.redefineSenha = "";

  inputNode.value = objCadastro.redefineSenha;
  pNode.appendChild(inputNode);
  fieldSet.appendChild(pNode);

  // button
  divNode = document.createElement("DIV");
  inputNode = document.createElement("INPUT");
  inputNode.type = "button";
  inputNode.id = "salvar";

  inputNode.disabled =  (cadastro_id&&cadastro_id>0)?true:false;

  if (cadastro_id && Number(cadastro_id) > 0)
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

async function loadCadastro(cadastro_id) {

  return new Promise((resolve, reject) => {
    
    //==>debug
    console.log("loadCadastro fetch call:", `/atendimentos/api/usuarios.php?id=${cadastro_id}&mode=json`);

    let headers = new Headers();
    headers.append('Accept', 'application/json');

    fetch(`/atendimentos/api/usuarios.php?id=${cadastro_id}&mode=json`, {
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
            console.log('loadCadastro json:', json);

            if (json.error) {
                writeToConsole(`loadTerminais error: ${JSON.stringify(json.error)}`);
                reject(json.error);
            }

            objCadastro = JSON.parse(JSON.stringify(json[cadastro_id]));
            objCadastro["cadastro_id"] = cadastro_id;

            savedObjCadastro = JSON.parse(JSON.stringify(objCadastro));

            //==>debug
            console.log('loadCadastro (response) objCadastro:', objCadastro);

            crateMainApp();

            resolve(objCadastro);
        })
        .catch((err) => {
            let error = `catch: fetch objCasdastro: ${err.message}`;
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
  cadastro_id = getParameterByName('cadastro_id');

  if( Number(cadastro_id) > 0) {
    objCadastro = await loadCadastro(cadastro_id);
  }

  //==>debug
  debug_log(2, "onLoadBody: casastro_id:", cadastro_id);

  crateMainApp();
})();
