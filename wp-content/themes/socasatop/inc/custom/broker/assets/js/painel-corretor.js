const { useState, useEffect } = React;

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('painel-corretor-root');
    if (container) {
        const root = ReactDOM.createRoot(container);
        root.render(React.createElement(PainelCorretor));
    }
});

const PainelCorretor = () => {
    const [totalImoveis, setTotalImoveis] = useState(0);
    const [imoveisPatrocinados, setImoveisPatrocinados] = useState(0);
    
    useEffect(() => {
        fetch(`${site.ajax_url}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_broker_properties&nonce=${site.nonce}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                setTotalImoveis(data.data.properties.length);
                setImoveisPatrocinados(data.data.properties.filter(p => p.sponsored).length);
            }
        });
    }, []);

    return React.createElement('div', { 
        className: "grid grid-cols-12 gap-4 p-4"
    }, [
        // Box Bem-vindo
        React.createElement('div', {
            className: "col-span-8 bg-[#1E56B3] rounded-2xl p-8 text-white h-48 relative",
            key: "welcome"
        }, [
            React.createElement('div', null, [
                React.createElement('h1', { 
                    className: "text-4xl font-bold mb-2"
                }, "Bem-vindo"),
                React.createElement('h2', { 
                    className: "text-3xl font-bold"
                }, `Sr. ${site.user_name},`)
            ]),
            React.createElement('div', {
                className: "absolute bottom-8 right-8"
            }, React.createElement('svg', {
                width: '40',
                height: '2',
                viewBox: '0 0 40 2',
                fill: 'none'
            }, React.createElement('line', {
                x1: '0',
                y1: '1',
                x2: '40',
                y2: '1',
                stroke: 'white',
                strokeWidth: '2'
            })))
        ]),

        // Box Estatísticas
        React.createElement('div', {
            className: "col-span-4 bg-gray-100 rounded-2xl p-6",
            key: "stats"
        }, [
            React.createElement('p', { 
                className: "text-xl font-medium mb-4"
            }, `${totalImoveis} Imóveis anunciados`),
            React.createElement('p', { 
                className: "text-xl font-medium"
            }, `${imoveisPatrocinados} Imóveis em destaques`)
        ]),

        // Box Botões
        React.createElement('div', {
            className: "col-span-4 space-y-4",
            key: "actions"
        }, [
            React.createElement('a', {
                href: "/adicionar-imoveis",
                className: "block bg-[#1E56B3] text-white rounded-2xl p-6 text-xl text-center"
            }, "Fazer novo anúncio"),
            React.createElement('a', {
                href: "/meus-imoveis",
                className: "block bg-[#1E56B3] text-white rounded-2xl p-6 text-xl text-center"
            }, "Fazer novo destaque")
        ]),

        // Box Estatísticas do Negócio
        React.createElement('div', {
            className: "col-span-8 bg-gray-100 rounded-2xl p-8 h-48",
            key: "statistics"
        }, React.createElement('h3', {
            className: "text-[#1E56B3] text-2xl font-bold"
        }, "Estátisticas do seu negócio.")),

        // Box Banner
        React.createElement('div', {
            className: "col-span-4 bg-[#1E56B3] rounded-2xl p-8 h-48",
            key: "banner"
        }, React.createElement('h3', {
            className: "text-white text-2xl font-bold"
        }, "Banner de novidades"))
    ]);
};