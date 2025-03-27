const ContactForm = ({ postId, brokerId }) => {
  const [formData, setFormData] = React.useState({
    name: '',
    email: '',
    whatsapp: ''
  });
  const [loading, setLoading] = React.useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await fetch(site.ajax_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'submit_contact_form',
          nonce: site.nonce,
          post_id: postId,
          broker_id: brokerId,
          ...formData
        })
      });

      const data = await response.json();
      
      if (data.success) {
        // Mostrar informações do corretor brevemente antes de redirecionar
        Swal.fire({
          title: 'Redirecionando para o WhatsApp',
          html: `
            <p>Você será redirecionado para conversar com o corretor via WhatsApp em alguns segundos...</p>
            <p><strong>Nome do corretor:</strong> ${data.data.broker.name}</p>
          `,
          icon: 'success',
          timer: 3000,
          timerProgressBar: true,
          showConfirmButton: false
        }).then(() => {
          // Remover o formulário
          const container = document.getElementById('immobile-contact-form');
          if (container) container.remove();
          
          // Redirecionar para o WhatsApp
          window.location.href = data.data.whatsapp_url;
        });
      } else {
        throw new Error(data.data || 'Erro ao processar solicitação');
      }
    } catch (error) {
      console.error('Erro:', error);
      Swal.fire({
        title: 'Erro',
        text: error.message || 'Ocorreu um erro ao processar sua solicitação',
        icon: 'error'
      });
    } finally {
      setLoading(false);
    }
  };

  React.useEffect(() => {
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = 'auto';
    };
  }, []);

  return (
    React.createElement('div', { 
      style: {
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        backgroundColor: 'rgba(0, 0, 0, 0.5)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        zIndex: 9999
      },
      onClick: (e) => {
        if (e.target === e.currentTarget) {
          const container = document.getElementById('immobile-contact-form');
          if (container) container.remove();
        }
      }
    }, [
      React.createElement('div', { 
        style: {
          backgroundColor: 'white',
          padding: '24px',
          borderRadius: '8px',
          boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)',
          width: '100%',
          maxWidth: '500px',
          position: 'relative',
          margin: '20px'
        },
        onClick: (e) => e.stopPropagation()
      }, [
        React.createElement('button', {
          type: 'button',
          onClick: () => {
            const container = document.getElementById('immobile-contact-form');
            if (container) container.remove();
          },
          style: {
            position: 'absolute',
            top: '10px',
            right: '10px',
            border: 'none',
            background: 'none',
            fontSize: '24px',
            cursor: 'pointer',
            color: '#666'
          },
          id: 'close-modal-button'
        }, '×'),

        React.createElement('h3', { 
          style: {
            fontSize: '1.25rem',
            fontWeight: 'bold',
            marginBottom: '1rem'
          }
        }, 'Falar com o Corretor'),
        
        React.createElement('p', {
          style: {
            marginBottom: '1rem',
            fontSize: '0.9rem'
          }
        }, 'Preencha seus dados para entrar em contato com o corretor via WhatsApp.'),
        
        React.createElement('form', { 
          onSubmit: handleSubmit,
          id: 'broker-contact-form'
        }, [
          React.createElement('div', { style: { marginBottom: '1rem' }}, [
            React.createElement('input', {
              type: 'text',
              placeholder: 'Seu nome',
              required: true,
              value: formData.name,
              onChange: (e) => setFormData({...formData, name: e.target.value}),
              style: {
                width: '100%',
                padding: '0.5rem',
                border: '1px solid #ddd',
                borderRadius: '4px'
              },
              id: 'contact-name',
              name: 'contact-name'
            })
          ]),
          
          React.createElement('div', { style: { marginBottom: '1rem' }}, [
            React.createElement('input', {
              type: 'email',
              placeholder: 'Seu email',
              required: true,
              value: formData.email,
              onChange: (e) => setFormData({...formData, email: e.target.value}),
              style: {
                width: '100%',
                padding: '0.5rem',
                border: '1px solid #ddd',
                borderRadius: '4px'
              },
              id: 'contact-email',
              name: 'contact-email'
            })
          ]),
          
          React.createElement('div', { style: { marginBottom: '1rem' }}, [
            React.createElement('input', {
              type: 'tel',
              placeholder: 'Seu WhatsApp',
              required: true,
              value: formData.whatsapp,
              onChange: (e) => setFormData({...formData, whatsapp: e.target.value}),
              style: {
                width: '100%',
                padding: '0.5rem',
                border: '1px solid #ddd',
                borderRadius: '4px'
              },
              id: 'contact-whatsapp',
              name: 'contact-whatsapp'
            })
          ]),
          
          React.createElement('button', {
            type: 'submit',
            disabled: loading,
            style: {
              width: '100%',
              padding: '0.5rem',
              backgroundColor: loading ? '#ccc' : '#4CAF50',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: loading ? 'not-allowed' : 'pointer'
            },
            id: 'submit-contact-form'
          }, loading ? 'Enviando...' : 'Continuar para o WhatsApp')
        ])
      ])
    ])
  );
};

window.openContactForm = function(brokerId, immobileId) {
  const form = document.createElement('div');
  form.id = 'immobile-contact-form';
  form.setAttribute('data-post-id', immobileId);
  form.setAttribute('data-broker-id', brokerId);
  document.body.appendChild(form);
  
  ReactDOM.render(
    React.createElement(ContactForm, { postId: immobileId, brokerId }),
    form
  );
};