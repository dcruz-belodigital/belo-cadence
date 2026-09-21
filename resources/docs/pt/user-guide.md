# Manual do utilizador

O Belo Cadence acompanha o email que os seus clientes devem receber segundo um calendário:
o que sai, quando sai, se saiu mesmo e exatamente o que foi enviado.

## Primeiros passos

Chega ao Belo Cadence com uma conta que alguém criou para si — não há página de registo.
Se não conseguir iniciar sessão, peça a um administrador que confirme se a sua conta está
ativa.

O que consegue ver e fazer depende dos **perfis de acesso** que tem. Se uma secção aqui
descrita não aparecer na sua barra lateral, os seus perfis não a incluem; isso é o
esperado e não uma avaria.

A sua primeira paragem é o **Painel**. Responde a uma pergunta: o que precisa de atenção?

- **A enviar hoje** — ocorrências cujo momento chegou, incluindo o que estiver em atraso.
- **A enviar nos próximos 7 dias** — o que aí vem.
- **Enviados este mês** e **Falharam este mês** — como têm corrido os envios.

Por baixo das métricas encontra as próximas notificações, as falhas mais recentes e a
atividade recente. Cada painel liga à lista completa.

## Clientes

Um **cliente** é uma organização que recebe email. Cada cliente tem um nome, um endereço
de email, um estado e notas internas — e tudo o mais que a sua equipa tenha decidido
registar sobre ele, através dos **atributos de cliente**.

- Os clientes **ativos** recebem o email agendado. Os clientes **inativos** mantêm todas
  as definições e todo o histórico, mas nunca recebem email.
- As **notas internas** nunca são enviadas a ninguém. São para a sua equipa.
- **Arquivar** um cliente retira-o da lista de trabalho e desliga os seus agendamentos.
  Nada é eliminado: todo o email já enviado permanece no histórico de envios e o cliente
  pode ser restaurado a partir da sua própria página.

Assinale *Incluir arquivados* na lista de clientes para voltar a encontrar um cliente
arquivado.

## Atributos de cliente

O Belo Cadence não decide o que mais precisa de saber sobre um cliente. **Administração →
Atributos de cliente** é onde a sua equipa o define: um nome, um tipo, e se é obrigatório.
Cada atributo ativo passa a aparecer no formulário de cliente, pela ordem que lhes der.

Os tipos são texto, texto longo, número, data, sim/não, escolha de uma lista, endereço
web, endereço de email e **linhas repetíveis** — um conjunto de campos que pode ser
preenchido as vezes que forem precisas, como uma lista de contactos. Uma linha repetível
pode, por sua vez, conter linhas repetíveis, até três níveis.

Um campo dentro de uma linha repetível precisa de um tipo, mas não de um nome. Deixe o
nome vazio e os valores aparecem sozinhos, sem etiqueta, o que transforma uma linha de um
só campo numa simples lista — de domínios, por exemplo.

Vale a pena saber:

- O **identificador** é definido a partir do nome quando o atributo é criado e nunca muda.
  É o nome da coluna nos ficheiros CSV, por isso mudar o nome de um atributo nunca parte um
  ficheiro ou um processo que já o use.
- O **tipo não pode ser alterado** depois, porque todas as respostas já registadas estão
  guardadas nesse formato. Para o mudar, desative o atributo e crie outro.
- Remover uma opção de uma lista deixa de a oferecer, mas um cliente que já a use mantém a
  sua resposta e continua editável.
- **Desativar** um atributo esconde-o do formulário de cliente, da página do cliente e de
  ambos os ficheiros CSV, mantendo todas as respostas. Volte a ativá-lo e continuam lá.
- **Eliminar** um atributo elimina mesmo as respostas com ele. A confirmação diz quantos
  clientes são afetados.

Marcar um atributo como obrigatório impede que um cliente seja guardado sem ele. Os
clientes criados antes disso ficam como estão até alguém os editar.

## Agendamentos de notificação

Um **agendamento** responde a: que email sai, para quem, e quando?

Cada agendamento envia para uma de duas coisas, decidida no momento em que o cria:

- **Um cliente.** O email vai para o endereço desse cliente e o agendamento fica
  registado nele, na sua própria página.
- **Uma lista de destinatários.** Dá um nome à notificação e indica os endereços para
  onde vai. Não pertence a nenhum cliente, e é assim que se agenda tudo o que não é sobre
  um cliente — um resumo interno, uma nota a um fornecedor, um lembrete para a sua equipa.

Crie um agendamento de cliente a partir da página do cliente, e qualquer um dos dois em
**Cadence → Agendamentos de notificação → Nova notificação**. Um agendamento nunca muda
o que envia: ao editá-lo pode mudar o nome, os endereços, o modelo e as datas, mas um
agendamento de cliente continua a ser de cliente.

Escolhe um dos modelos de email, uma frequência e a data e hora do primeiro envio. Daí em
diante o Belo Cadence calcula sozinho cada ocorrência seguinte.

- **Uma vez** — enviado uma única vez, e depois o agendamento fecha-se sozinho.
- **Mensal** — todos os meses no mesmo dia.
- **Anual** — todos os anos na mesma data.

A data do primeiro envio é a **âncora** da recorrência, e o dia do mês nunca se perde. Um
agendamento mensal ancorado no dia 31 recua para o último dia dos meses mais curtos e
regressa ao dia 31 a seguir. Um agendamento anual ancorado a 29 de fevereiro usa 28 de
fevereiro nos anos comuns e volta a 29 de fevereiro nos anos bissextos.

As horas são sempre mostradas e introduzidas no **seu** fuso horário, que define no seu
perfil. A recorrência em si é calculada no fuso horário da aplicação, para que um
agendamento mantenha a hora local pretendida durante todo o ano.

**Desativar** um agendamento faz com que deixe de enviar, mas mantém a âncora, para que
possa retomá-lo mais tarde. Ao reativá-lo, ele segue a partir da próxima ocorrência
futura — as ocorrências perdidas enquanto esteve desligado não são enviadas em catadupa.

**Eliminar** um agendamento retira-o das listas de trabalho e mantém tudo o que já
enviou.

**Enviar agora**, em qualquer agendamento, envia de imediato a mensagem desse agendamento
para os seus próprios destinatários. A recorrência fica intacta: nada é reagendado e
nenhuma ocorrência é gasta. Fica registado como envio manual.

Um agendamento de cliente só envia enquanto o cliente estiver ativo e não arquivado — o
que também se aplica ao Enviar agora. Uma lista de destinatários não depende do estado de
ninguém.

## Próximos

**Próximos** é a resposta a "o que sai a seguir?", do mais próximo para o mais distante.
Pode limitar a hoje, aos próximos 7 dias ou aos próximos 30, ou filtrar por cliente,
modelo, frequência e estado. O que estiver a ver pode ser exportado tal como está.

## Modelos de email

Os modelos fazem parte da aplicação e não são algo que se edite no navegador. Isso é
deliberado: significa que o texto de um email é revisto como qualquer outra alteração e
não pode ser mudado por acidente.

Cada modelo é escrito para um leitor em particular, e isso decide onde pode ser usado:

- **Para um cliente** — o texto dirige-se ao cliente de que trata. Só os agendamentos de
  cliente os podem usar.
- **Para uma lista de destinatários** — o texto dirige-se a quem foi colocado na lista. Só
  os agendamentos de lista os podem usar.
- **Para qualquer agendamento** — o modelo **Em branco**, que ambos podem usar.

O formulário de agendamento só oferece os modelos que servem para o que está a enviar, por
isso um cliente nunca recebe texto escrito para uma lista.

**Em branco** é a única exceção à regra de o texto viver na aplicação: não traz texto
nenhum, e é você que escreve o assunto e a mensagem no próprio agendamento. É texto
simples — deixe uma linha em branco entre parágrafos — e é enviado exatamente como o
escrever, dentro da moldura normal do email. Use-o para o caso pontual que nenhum modelo
cobre; para o que envia repetidamente use um modelo a sério, porque esse texto é revisto.

Abra **Cadence → Modelos de email** para ler cada um exatamente como chega, com um nome
de exemplo preenchido e a indicação de para quem foi escrito. Também pode pré-visualizar
um modelo a partir do formulário de agendamento enquanto o escolhe.

Acrescentar um modelo é uma pequena tarefa de desenvolvimento: uma entrada no catálogo, o
seu público, uma vista, o texto e um teste.

Alguns modelos deixam **espaços** para preencher, como uma data de renovação. Quando
escolhe um desses num agendamento — ou ao enviar à mão — aparece um seletor para cada
espaço. Aponte-o a um dos atributos do cliente, incluindo um único campo dentro de linhas
repetíveis, ou escolha *Escrever um valor* e indique algo usado apenas por essa
notificação.

Se um espaço apontar para um atributo sem resposta nesse cliente, a notificação **não** é
enviada: a tentativa fica registada no histórico de envios como falhada, com o motivo,
para que ninguém receba um email incompleto.

## Enviar manualmente

Às vezes algo tem de sair agora e não há agendamento que o cubra. Há duas maneiras.

**A partir de um agendamento.** Abra o agendamento e prima **Enviar agora**. Envia a
mensagem desse agendamento para os seus destinatários e não muda nada na recorrência.

**Sem agendamento.** Abra **Cadence → Histórico de envios → Enviar agora**, ou use
**Enviar agora** na página de um cliente. Escolha um cliente ou escreva você mesmo os
endereços, escolha um modelo e confirme — não é preciso que exista um agendamento. O email
sai de imediato.

Só são oferecidos clientes **ativos**. Marcar um cliente como inativo, ou arquivá-lo, é a
forma de dizer "deixem de lhe enviar email", por isso um envio manual não contorna essa
decisão. Os endereços que escreve são da sua responsabilidade; nada os verifica além de
serem endereços válidos.

Um envio manual fica registado no histórico de envios exatamente como um agendado,
marcado como **Manual** e com o nome de quem o enviou. Nada muda em nenhum agendamento:
nenhuma âncora se mexe, nenhuma ocorrência é gasta. Enviar duas vezes envia duas
vezes — não há ocorrência que possa ser duplicada, por isso pedir outra vez é pedir outra
vez.

Pode restringir o histórico de envios a envios manuais ou agendados com o filtro
**Origem**, e a distinção acompanha as duas exportações.

## Histórico de envios

Todas as tentativas de envio são registadas, com ou sem sucesso. Um envio é **prova**:
guarda o destinatário, o remetente, o assunto e a mensagem exata que foi produzida, e
nada disso é alguma vez recalculado. Se o nome ou o endereço de um cliente mudar amanhã,
ou se o remetente mudar, ou se um modelo for reescrito, um envio antigo continua a
mostrar o que realmente saiu.

**Um endereço, um envio.** Uma notificação que vai para cinco pessoas são cinco envios, um
por endereço. É isso que permite que um único endereço rejeitado apareça sozinho em vez de
se esconder atrás dos quatro que chegaram, e é por isso que uma lista de destinatários
produz várias linhas para a mesma ocorrência.

Cada envio regista **para onde foi** — um cliente, ou o nome que a lista tinha na altura —
e esse nome é guardado como tudo o resto, por isso mudar o nome de uma lista nunca reescreve
o seu histórico.

Abra um envio para ver a mensagem tal como foi enviada, quando foi agendada, quando foi
tentada e — se falhou — porquê.

O histórico de envios não pode ser editado nem importado, por princípio. Pode ser
pesquisado, filtrado e exportado.

Cada ocorrência é enviada **uma só vez por endereço**. Correr o agendador duas vezes, ou manualmente
enquanto já está a correr, não consegue produzir um segundo email para a mesma
ocorrência.

## Quando algo falha

Um envio falhado é guardado, marcado como falhado com o respetivo motivo, e origina uma
notificação para todos os que conseguem ver o histórico de envios. O agendamento passa
depois à ocorrência seguinte, para que uma tarde má não bloqueie a série inteira.

Comece por olhar para a mensagem de falha na página do envio: normalmente identifica o
problema (um servidor de email que recusou a ligação, um endereço rejeitado). Depois de
corrigida a causa, a ocorrência seguinte é enviada normalmente; se a mensagem perdida
continuar a ser importante, crie um agendamento de uma só vez para ela.

## Importar e exportar

Todas as listas podem ser exportadas de duas formas:

- **Esta vista em CSV** — as colunas que vê, formatadas para uma pessoa, respeitando a
  sua pesquisa, filtros e ordenação atuais.
- **CSV em bruto** — todas as colunas guardadas, com valores em bruto e datas ISO, para
  outro sistema.

Os clientes e os utilizadores também podem ser **importados**:

1. **Descarregue o modelo** para que as suas colunas correspondam.
2. **Carregue** o seu ficheiro.
3. **Faça corresponder as colunas** — o Belo Cadence corresponde automaticamente pelo
   nome e mostra-lhe as primeiras linhas, para que veja o que entendeu antes de se
   guardar seja o que for.
4. **Importe.** Cada linha é verificada com as mesmas regras dos formulários. Se uma
   linha estiver errada, não é importado absolutamente nada e é-lhe dito qual a linha e
   porquê.

Deixe a coluna `id` vazia para criar um registo; preencha-a com um id existente para
atualizar esse registo. Os registos importados passam pelos mesmos passos que os
introduzidos à mão, por isso aparecem também no registo de auditoria.

O histórico de envios e o registo de auditoria **não** são importáveis, de propósito: são
o registo do que aconteceu.

Os atributos personalizados recebem uma coluna cada um, chamada `attribute_` seguido do
identificador do atributo. Uma coluna que não associe fica intocada, por isso um ficheiro
que apenas corrige endereços de email nunca mexe em mais nada. As linhas repetíveis viajam
em JSON numa única célula, e é por isso que a exportação **em bruto** é a que pode ser
importada de volta tal como está.

## Utilizadores e perfis de acesso

As contas são criadas dentro do Belo Cadence. Uma pessoa pode ter mais do que um perfil
de acesso, e um perfil é um conjunto de permissões.

- As permissões fazem parte da aplicação; escolhe apenas quais delas um perfil concede.
- Desativar alguém impede-o de iniciar sessão e mantém tudo o que essa pessoa fez.
- Não pode desativar-se a si próprio, e a aplicação recusa qualquer alteração que deixe
  ninguém com possibilidade de gerir utilizadores e perfis de acesso.

## Registo de auditoria

O registo de auditoria regista quem alterou o quê: clientes, agendamentos, utilizadores,
perfis de acesso, definições e as notificações predefinidas, com os valores antes e
depois. O trabalho feito pelo agendador não tem uma pessoa por trás e é registado como
**Sistema**.

É apenas de leitura. As visitas normais a páginas não são registadas — só as alterações
que vale a pena guardar.

## Definições

As **definições da aplicação** guardam o nome da aplicação, o idioma e o fuso horário
predefinidos, o **tema predefinido** e o nome e endereço de quem envia o email a
clientes. Alterar o remetente afeta apenas o email futuro. As credenciais do servidor de
email não são editáveis aqui de propósito: pertencem à configuração de ambiente do
servidor.

O tema predefinido é o que toda a gente vê enquanto não escolher um próprio, por isso
alterá-lo veste de novo a aplicação para toda a equipa de uma só vez.

As **notificações predefinidas**, mais abaixo na mesma página, são os agendamentos que a
sua equipa costuma aplicar a um novo cliente. São oferecidos quando se cria um cliente; aplicar um **copia**-o para
esse cliente. Alterar esta lista mais tarde nunca altera um agendamento que já exista.

## O seu perfil

O seu perfil guarda o seu nome, a sua palavra-passe e as suas preferências:

- **Esquema de cores** — sistema, claro ou escuro. Também se muda a qualquer momento a
  partir do menu da conta.
- **Tema** — qual dos aspetos abaixo a aplicação veste para si. Deixe por definir para
  seguir aquele que um administrador tiver definido como predefinido, incluindo
  alterações posteriores.
- **Idioma** e **Fuso horário** — o fuso horário decide como todas as datas e horas da
  aplicação lhe são mostradas. O idioma também se muda a partir do menu da conta.

Só você vê o seu tema e o seu esquema de cores; nenhum deles muda o que quer que seja do
que um cliente recebe.

Os seus perfis de acesso e o facto de a sua conta estar ativa são geridos por um
administrador.

## Temas

Um tema decide em conjunto a cor, o formato dos cantos e o tipo de letra. Cada um é
desenhado em claro e em escuro, por isso o tema e o esquema de cores são escolhas
separadas.

- **Iris** — minimalista e moderno, em violeta suave. O predefinido.
- **Cappuccino** — clássico e de cantos direitos: papel quente, tinta de café e uma
  serifa para os títulos.
- **Bubblegum** — vistoso, redondo e bem-disposto.
- **Graphite** — sóbrio e quase monocromático, tinta sobre papel, com a cor reservada
  para o que comunica um estado.
- **Cathode** — um terminal: monoespaçado do princípio ao fim, direito, verde de fósforo.

O símbolo na barra lateral — um avião de papel dobrado — é desenhado com a cor e o
formato de cantos do próprio tema, para que perceba num relance qual está a usar.
