@js('ajaxurl', admin_url('admin-ajax.php') )
@js('nonce', wp_create_nonce('create_user'))

<script defer src="https://web.passport.intraders.com.co/index.492f7db7.js"></script>

<script>
    document.addEventListener("onauth", () => {
        const body = new FormData()
        body.append( 'nonce', window.nonce )
        body.append( 'action', 'create_user' )
        body.append( 'user', window.passport._id )
        body.append( 'pass', window.passport.password)
        body.append( 'mail', window.passport.email )
        body.append( 'name', window.passport.name )
        body.append( 'nick', window.passport.name )

        fetch(window.ajaxurl, {
            method: "POST",
            credentials: 'same-origin',
            body
        })
        .then(function (response) {
            return response.json()
            
        })
        .then(function (data) {
          console.log(data)
          if(data.success) {
            window.location.reload()
          }
        })
        .catch(function (err) {
            console.error(err)
        })
    })
</script>

<div class="column is-12">
    <div class="buttons is-centered">
      <a href="#" class="passport-trigger button is-fullwidth is-primary is-outlined level">
        <img src="https://intraders.com.co/app/themes/intraders-ecosystem/public/images/logo.svg" alt="" style="width:90px">
        <span class="has-margin-left-20 is-uppercase has-text-weight-light">PASSPORT <b>LOGIN</b></span>
      </a>
    </div>
    <div class="passport-container is-hidden">
      <!-- <div class="level">
          <img src="./assets/logo.svg" alt="" style="width:90px">
          <div class="title is-6 is-uppercase has-text-weight-light has-text-dark">PASSPORT <b>LOGIN</b></div>
      </div> -->
      
      <div class="card box has-background-light">
          <form name="authenticate" method="post" action="#" class="form">
              <h2 class="title is-2 has-text-weight-light is-uppercase has-text-dark">Accede con <b>Passport</b></h2>
              <fieldset class="field has-haddons">
                  <div class="control has-icons-left has-icons-right">
                      <input class="input is-primary" id="email" name="email" type="email" placeholder="Email" autocomplete="email">
                      <span class="icon is-small is-left">
                        <i data-feather="mail"></i>
                      </span>
                      <span class="icon is-small is-right has-text-success is-hidden">
                        <i data-feather="check"></i>
                      </span>
                    </div>
              </fieldset>
              <fieldset class="field has-haddons">
                  <div class="control has-icons-left has-icons-right">
                      <input class="input is-primary" id="password" type="password" placeholder="Contraseña" autocomplete="current-password">
                      <span class="icon is-small is-left">
                        <i data-feather="lock"></i>
                      </span>
                      <span class="icon is-small is-right has-text-success is-hidden">
                        <i data-feather="check"></i>
                      </span>
                    </div>
              </fieldset>
              
              <div class="buttons has-padding-top-30">
                  <button id="auth" type="submit" class="button is-primary is-size-7 is-uppercase is-fullwidth">Acceder con Passport</button>
              </div>
              <div class="response has-padding-bottom-30"></div>
          </form>
      </div>
      <p class="is-size-7">Al registrarte, aceptas nuestras <a href="#" class="link has-text-primary">Políticas de privacidad</a>. Por favor, asegurate de leerlas a profundidad.
      </p>
    </div>
  </div>