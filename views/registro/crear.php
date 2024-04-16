<main class="registro">
    <h2 class="registro__heading"><?php echo $titulo; ?></h2>
    <p class="registro__descripcion">Elige tu plan</p>

    <div class="paquetes__grid">
        <div class="paquete">
            <h3 class="paquete__nombre">Pase Gratis</h3>
            <ul class="paquete__lista">
                <li class="paquete__elemento">Acceso Virtual a DevWebCamp</li>
            </ul>

            <p class="paquete__precio">$0</p>

            <form method="post" action="/finalizar-registro/gratis">
                <input type="submit" class="paquetes__submit" value="Inscripcion gratis">
            </form>
        </div>

        <div class="paquete">
            <h3 class="paquete__nombre">Pase Presencial</h3>
            <ul class="paquete__lista">
                <li class="paquete__elemento">Acceso Presencial a DevWebCamp</li>
                <li class="paquete__elemento">Pase por 2 días</li>
                <li class="paquete__elemento">Acceso a talleres y conferencias</li>
                <li class="paquete__elemento">Acceso a las grabaciones</li>
                <li class="paquete__elemento">Camisa del evento</li>
                <li class="paquete__elemento">Comida y bebida</li>
            </ul>

            <p class="paquete__precio">$199</p>
            <div id="paypal-container-MQ5WPZ4GAKGYU"></div>
        </div>

        <div class="paquete">
            <h3 class="paquete__nombre">Pase Virtual</h3>
            <ul class="paquete__lista">
                <li class="paquete__elemento">Acceso Virtual a DevWebCamp</li>
                <li class="paquete__elemento">Pase por 2 días</li>
                <li class="paquete__elemento">Acceso a talleres y conferencias</li>
                <li class="paquete__elemento">Acceso a las grabaciones</li>
            </ul>

            <p class="paquete__precio">$49</p>
            <div id="paypal-container-XXXXXX"></div>
        </div>
    </div>
</main>

<script src="https://www.paypal.com/sdk/js?client-id=AblDgvXU-_MnrlTh6_DrOJUbdax_05SbLU4UprRrKpSTkBTRoNmreZMM76YfIFaz9Dby9BADfD3aB7kl&components=buttons&disable-funding=venmo&currency=USD"></script>
 
<script>
    function initPayPalButton() {

      // PASE PRESENCIAL
      paypal.Buttons({
        style: {
          shape: 'rect',
          color: 'blue',
          layout: 'vertical',
          label: 'pay',
        },
 
        createOrder: function(data, actions) {
          return actions.order.create({
            purchase_units: [{"description":"1","amount":{"currency_code":"USD","value":199}}]
          });
        },
 
        onApprove: function(data, actions) {
          return actions.order.capture().then(function(orderData) {
 
            // Full TRAZA DE LA OPERACION - available details
            //console.log('Capture result', orderData, JSON.stringify(orderData, null, 2));
 
            const datos = new FormData();
            datos.append('paquete_id', orderData.purchase_units[0].description);
            datos.append('pago_id', orderData.purchase_units[0].payments.captures[0].id);

            fetch('/finalizar-registro/pagar', {
                method: 'POST',
                body: datos
            })
            .then(respuesta => respuesta.json())
            .then(resultado => {
                if(resultado.resultado) {
                    actions.redirect(window.location.origin +`/finalizar-registro/conferencias`);
                }
            })
            
          });
        },
 
        onError: function(err) {
          console.log(err);
        }
      }).render('#paypal-container-MQ5WPZ4GAKGYU');

      // PASE VIRTUAL
      paypal.Buttons({
        style: {
          shape: 'rect',
          color: 'blue',
          layout: 'vertical',
          label: 'pay',
        },
 
        createOrder: function(data, actions) {
          return actions.order.create({
            purchase_units: [{"description":"2","amount":{"currency_code":"USD","value":49}}]
          });
        },
 
        onApprove: function(data, actions) {
          return actions.order.capture().then(function(orderData) {
 
            // Full TRAZA DE LA OPERACION - available details
            //console.log('Capture result', orderData, JSON.stringify(orderData, null, 2));
 
            const datos = new FormData();
            datos.append('paquete_id', orderData.purchase_units[0].description);
            datos.append('pago_id', orderData.purchase_units[0].payments.captures[0].id);

            fetch('/finalizar-registro/pagar', {
                method: 'POST',
                body: datos
            })
            .then(respuesta => respuesta.json())
            .then(resultado => {
                if(resultado.resultado) {
                    actions.redirect(window.location.origin +`/finalizar-registro/conferencias`);
                }
            })
            
          });
        },
 
        onError: function(err) {
          console.log(err);
        }
      }).render('#paypal-container-XXXXXX');
    }
 
  initPayPalButton();
</script>