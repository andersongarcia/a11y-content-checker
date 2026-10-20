# A11y Content Checker

[English](README.md)

Plugin de WordPress que verifica a acessibilidade do conteúdo **enquanto ele é produzido** e mostra a quem publica o que corrigir. As regras estão ligadas às WCAG 2.2 e à ABNT NBR 17225:2025.

> Estado: **protótipo inicial (0.1.0)**. Testado em um multisite local com WordPress 7.1, PHP 8.2 e Elementor 4.3.

## Por quê

Depois da primeira rodada de correções, as avaliações automáticas do tema costumam voltar limpas. O que se degrada com o tempo é o **conteúdo**, publicado todo dia por pessoas diferentes. Avaliar página por página não escala; verificar no momento da publicação, sim.

Editoria11y e Sa11y já fazem verificação no front-end para quem está logado e merecem ser conhecidos. Este plugin se concentra no que eles deixam de fora:

1. **Regras de conteúdo** ligadas a critérios das WCAG e a itens da NBR 17225, como link para arquivo sem formato, cartaz publicado como imagem e texto em maiúsculas.
2. **Resultado gravado por publicação**, o que permite listar as pendências do site inteiro e gerar relatório de uma rede multisite pela linha de comando.
3. **Link para um checklist humano**, que cobre o que a máquina não decide.

## Como funciona

Duas camadas:

| Camada | O que verifica | Por quê |
|---|---|---|
| **Servidor (PHP)** | Regras de conteúdo sobre o HTML renderizado | Roda ao salvar e em lote, sem navegador. É o que dá escala. |
| **Navegador (axe-core)** | Regras WCAG A e AA na página real, incluindo contraste | Só o navegador calcula estilos. Funciona com qualquer editor, inclusive Elementor. |

O resultado aparece numa coluna "Acessibilidade" nas listas de posts e páginas, num relatório em Ferramentas › Acessibilidade, no painel de pré-publicação do editor de blocos e num modo de revisão na barra de administração. Em todos esses lugares a interface fica em português quando o WordPress está em pt-BR, inclusive as mensagens do axe-core.

Decisões:

- **Informa, não bloqueia.** Impedir a publicação gera atalhos. Um bloqueio opcional de erros pode vir depois.
- **Nada carregado de terceiros.** O axe-core vai empacotado em `vendor/`.
- **Só conteúdo.** Problemas do tema ficam com a avaliação por modelo de página.

As regras, os ganchos de extensão, o comando de linha e o ambiente de desenvolvimento estão descritos no [README em inglês](README.md).

## Licença

GPL-2.0-or-later. Inclui o [axe-core](https://github.com/dequelabs/axe-core) (MPL-2.0).

Desenvolvido por [Anderson Canale Garcia](https://github.com/andersongarcia) como parte da sua pesquisa em acessibilidade digital.
