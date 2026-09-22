# Registo de alterações

## 22 de setembro de 2026

### Novidades

- **Mais quatro temas.** O **Meridian** é frio e preciso, em azul-marinho profundo; o
  **Ember** mantém uma página de carvão quase neutra e reserva a cor para o destaque; o
  **Folio** é livresco, compondo a página toda numa serifa sobre papel marfim, com um
  destaque cor de vinho; e o **Manuscript** é escrito à mão — papel creme, tinta
  azul-preta, cantos direitos e a página toda em letra manuscrita. Como os cinco
  anteriores, cada um é desenhado em claro e em escuro. Escolha o seu no perfil ou defina
  o predefinido para todos em Definições.
- **Os atributos de cliente podem guardar um ficheiro.** O novo tipo **Ficheiro** aceita um
  carregamento até 10 MB — documentos, folhas de cálculo, apresentações, ficheiros de dados
  simples, imagens e arquivos zip. Uma linha repetível também pode guardar um, por isso
  "um ficheiro por certificado" é um único atributo. Substituir ou remover um ficheiro
  apaga o anterior em definitivo, tanto do registo como do armazenamento, e apagar um
  atributo de ficheiro leva consigo todos os ficheiros carregados para ele.
- **Os ficheiros carregados nunca são alcançáveis por uma ligação isolada.** São servidos
  apenas através do Belo Cadence, a quem tenha sessão iniciada e possa ler esse cliente.
  Partilhe o endereço e qualquer outra pessoa recebe uma página de início de sessão.
- **As notificações podem levar anexos.** Assinale os atributos de ficheiro do cliente em
  **Anexos**, num agendamento ou no envio manual; um ficheiro dentro de linhas repetíveis
  anexa um ficheiro por linha. Cada email leva aquilo que o cliente tiver no momento em que
  sai, por isso substituir um contrato atualiza todas as notificações que o anexam. Um
  ficheiro que não seja encontrado faz falhar o envio em vez de enviar um email sem ele, e
  o histórico de envios regista o nome de tudo o que seguiu.

## 18 de setembro de 2026

### Novidades

- **Os clientes podem guardar tudo o mais que a sua equipa regista sobre eles.** Em
  Administração → Atributos de cliente define os campos: texto, números, datas, sim/não,
  escolha de uma lista, endereços web e de email, e linhas repetíveis — um conjunto de
  campos preenchido as vezes que forem precisas, que por sua vez pode conter linhas
  repetíveis. Cada atributo ativo aparece no formulário de cliente, na página do cliente e
  em ambos os ficheiros CSV.
- **Os atributos podem ser importados e exportados.** Cada um recebe a sua própria coluna,
  com o nome do seu identificador. Uma coluna que não associe durante uma importação fica
  intocada, por isso um ficheiro que apenas corrige endereços de email não altera mais nada.
- **Os modelos de email podem lê-los.** Um modelo que deixa um espaço — o lembrete anual
  passa a deixar dois — oferece um seletor no formulário de agendamento: preencha-o a
  partir de um dos atributos do cliente, incluindo um único campo dentro de linhas
  repetíveis, ou escreva um valor usado apenas por essa notificação. Um espaço apontado a
  uma resposta que o cliente não tem regista o envio como falhado, em vez de enviar um
  email incompleto.
- **Desativar um atributo mantém as suas respostas.** Desativar esconde-o em todo o lado
  sem perder nada; eliminar diz primeiro quantos clientes seriam afetados.

## 21 de agosto de 2026

### Novidades

- **As notificações já não têm de ser sobre um cliente.** Um agendamento passa a enviar ou
  para um cliente, como antes, ou para uma **lista de destinatários**: dá um nome à
  notificação e indica os endereços para onde vai, e ela não pertence a nenhum cliente. É
  assim que se agenda tudo o que não é sobre um cliente — um resumo interno, uma nota a um
  fornecedor, um lembrete para a sua equipa. Crie qualquer um dos dois em Cadence →
  Agendamentos de notificação → Nova notificação, ou um de cliente na página do próprio
  cliente. Um agendamento nunca muda o que envia.
- **Os modelos passam a dizer para quem foram escritos.** Cada modelo é para um cliente,
  para uma lista de destinatários, ou para qualquer agendamento, e um formulário só oferece
  os que servem para o que está a enviar — por isso um cliente nunca recebe texto escrito
  para uma lista. Chegam com isto dois modelos novos para listas: **Atualização de estado**
  e **Ação necessária**.
- **Um modelo Em branco**, utilizável por ambos os tipos, em que escreve o assunto e a
  mensagem no próprio agendamento. É texto simples, enviado exatamente como o escrever,
  dentro da moldura normal do email. O texto de todos os outros modelos continua na
  aplicação, revisto como qualquer outra alteração.
- **Envie qualquer notificação a qualquer pessoa, a qualquer momento.** O Enviar agora de
  qualquer agendamento envia de imediato a mensagem desse agendamento para os seus
  destinatários, sem mexer na recorrência. O formulário de Enviar agora passa também a
  aceitar um nome e endereços escritos na hora, por isso um aviso pontual não precisa de
  nenhum agendamento. Ambos ficam registados como envios manuais, com o nome de quem os
  enviou.
- **Um endereço, um envio.** Uma notificação que vai para cinco pessoas passa a ser cinco
  entradas no histórico de envios em vez de uma, para que um único endereço rejeitado
  apareça sozinho em vez de se esconder atrás dos quatro que chegaram. Cada envio registra
  para onde foi — um cliente, ou o nome que a lista tinha na altura — guardado como tudo o
  resto.

### Alterações

- **"Notificação a cliente" passa a ser apenas "notificação"** em toda a aplicação, porque
  uma notificação já não tem de envolver um cliente. Os agendamentos, o histórico de
  envios, as suas permissões e o registo de auditoria acompanham. As entradas de auditoria,
  as permissões atribuídas e o histórico existentes são migrados, por isso nada se perde e
  nenhum perfil perde acessos — mas uma permissão chamada `client-notifications.*` em
  qualquer integração externa passa a ser `notifications.*`.
- **Um filtro Envia para** na lista de agendamentos e nos Próximos, para ver só
  agendamentos de cliente ou só listas de destinatários. As duas exportações levam a
  distinção.
- **A lista de agendamentos e os Próximos passam a nomear a notificação** e não apenas o
  seu cliente, para que uma lista de destinatários se leia corretamente.

- **Um filtro de origem no histórico de envios**, para ver só os envios que alguém pediu
  ou só os do agendador. As duas exportações levam a distinção.
- **O envio manual tem uma permissão própria**, por isso pode ser concedido separadamente
  da gestão de agendamentos. Só são oferecidos clientes ativos: marcar um cliente como
  inativo continua a significar "deixem de lhe enviar email", e isso também vale para o
  Enviar agora.

## 14 de agosto de 2026

### Novidades

- **O Belo Cadence fala português.** Toda a interface, o email a clientes, este manual e
  este registo de alterações estão disponíveis em português europeu. Mude de idioma a
  partir do menu da conta ou do seu perfil; os administradores definem o idioma
  predefinido para todos nas Definições.
- **Cinco temas à escolha.** Iris (minimalista e moderno, o predefinido), Cappuccino
  (clássico e de cantos direitos), Bubblegum (vistoso e redondo), Graphite (quase
  monocromático) e Cathode (um terminal monoespaçado). Cada um decide em conjunto a cor,
  o formato dos cantos e o tipo de letra, e cada um é desenhado em claro e em escuro. Os
  administradores definem o predefinido para todos nas Definições; qualquer pessoa pode
  escolher o seu no perfil, ou deixá-lo por definir para seguir o predefinido à medida
  que este muda.
- **O Belo Cadence chegou.** Email recorrente a clientes num só lugar: os clientes, os
  agendamentos que decidem o que recebem e um registo permanente de tudo o que foi
  enviado.
- **Agendamentos com três frequências.** Uma vez, mensal e anual. A data do primeiro
  envio é a âncora da recorrência, por isso um agendamento mensal definido no dia 31
  recua para o último dia dos meses mais curtos e regressa depois ao dia 31, e um
  agendamento anual definido a 29 de fevereiro usa 28 de fevereiro nos anos comuns.
- **Próximos.** O que sai a seguir, do mais próximo para o mais distante, limitado a
  hoje, aos próximos 7 dias ou aos próximos 30, e filtrável por cliente, modelo,
  frequência e estado.
- **Um histórico de envios em que pode confiar.** Cada tentativa guarda o destinatário, o
  remetente, o assunto e a mensagem exata que foi produzida. Nada é recalculado mais
  tarde, por isso um envio antigo continua a mostrar o que realmente saiu depois de um
  cliente, um remetente ou um modelo mudarem. Cada ocorrência é enviada uma só vez, mesmo
  que o agendador se sobreponha.
- **Falhas que pedem atenção.** Um envio falhado é guardado e marcado com o respetivo
  motivo, origina uma notificação para todos os que conseguem ver o histórico de envios,
  e o agendamento passa à ocorrência seguinte.
- **Modelos de email como código.** O texto de um email a clientes é revisto como
  qualquer outra alteração, em vez de ser editado no navegador, e cada modelo pode ser
  lido exatamente como um cliente o recebe em Cadence → Modelos de email.
- **Notificações predefinidas.** Os agendamentos que a sua equipa costuma aplicar a um
  novo cliente são oferecidos durante a criação. Aplicar uma predefinição cria uma cópia,
  por isso alterar a lista mais tarde nunca altera um agendamento que já exista.
- **CSV para dentro e para fora.** Todas as listas exportam ou a vista que está a ver ou
  os dados em bruto para outro sistema. Os clientes e os utilizadores importam de CSV com
  um passo de correspondência de colunas e uma pré-visualização das suas linhas; se uma
  linha estiver errada, não é importado nada.
- **Utilizadores, perfis de acesso e permissões.** Os perfis agrupam permissões, as
  pessoas podem ter vários, e a aplicação recusa qualquer alteração que deixe ninguém com
  possibilidade de gerir acessos.
- **Registo de auditoria.** Quem alterou o quê, com os valores antes e depois. O trabalho
  feito pelo agendador é registado como Sistema em vez de ser atribuído a uma pessoa.
- **Esquemas de cores claro, escuro e do sistema**, o seu próprio fuso horário e idioma,
  tudo a partir do seu perfil ou do menu da conta.
- **Este manual e este registo de alterações**, escritos em markdown e mantidos junto da
  aplicação.

### Alterações

- **Um novo símbolo.** O Belo Cadence é agora um avião de papel dobrado em vez de um
  relógio, na barra lateral, no ecrã de início de sessão e no separador do navegador.
  Dentro da aplicação é desenhado com a cor e o formato de cantos do tema atual; no
  separador mantém-se numa tinta neutra que se inverte conforme o aspeto claro ou escuro
  do próprio navegador.
- **As notificações em prazo passam a ser processadas de hora a hora** em vez de a cada
  cinco minutos. São lembretes com uma cadência mensal ou anual, por isso o que interessa
  é a hora a que a mensagem sai; um agendamento definido para as 09:00 continua a enviar
  às 09:00.
- **As páginas de importação começam pelo ficheiro.** Escolher um ficheiro passa a ser a
  primeira coisa da página, o botão diz apenas Continuar e o modelo é oferecido como uma
  ligação ao lado, em vez de um segundo botão. A referência das colunas vem a seguir. A
  explicação passo a passo mudou-se para o manual do utilizador, para o qual todas as
  páginas de importação passam a remeter.
- **As notificações predefinidas passam a ser uma secção das Definições** em vez de uma
  página própria, para que tudo o que um administrador configura uma vez esteja no mesmo
  sítio. Quem as pode alterar não muda: a secção só aparece a quem já chegava à página
  anterior, e continua a registar a sua própria entrada de auditoria.
- **"Tema" passa a significar o aspeto da aplicação**, e o interruptor claro/escuro
  chama-se **esquema de cores**. O controlo no menu da conta mantém-se igual — muda
  apenas o nome.

### Correções

- Os botões que submetem ou alternam passam a mostrar o cursor de mão, para que os
  controlos de idioma, esquema de cores e terminar sessão deixem de parecer não
  clicáveis.
- **Terminar sessão** passa a ser desenhado numa só cor, em vez de texto encarnado ao
  lado de um ícone cinzento.
