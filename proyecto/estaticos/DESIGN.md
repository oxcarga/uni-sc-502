---
name: Pulso Solidario
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#3d4947'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#6d7a77'
  outline-variant: '#bcc9c6'
  surface-tint: '#006a61'
  primary: '#00685f'
  on-primary: '#ffffff'
  primary-container: '#008378'
  on-primary-container: '#f4fffc'
  inverse-primary: '#6bd8cb'
  secondary: '#545f73'
  on-secondary: '#ffffff'
  secondary-container: '#d5e0f8'
  on-secondary-container: '#586377'
  tertiary: '#b90538'
  on-tertiary: '#ffffff'
  tertiary-container: '#dc2c4f'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#89f5e7'
  primary-fixed-dim: '#6bd8cb'
  on-primary-fixed: '#00201d'
  on-primary-fixed-variant: '#005049'
  secondary-fixed: '#d8e3fb'
  secondary-fixed-dim: '#bcc7de'
  on-secondary-fixed: '#111c2d'
  on-secondary-fixed-variant: '#3c475a'
  tertiary-fixed: '#ffdadb'
  tertiary-fixed-dim: '#ffb2b7'
  on-tertiary-fixed: '#40000d'
  on-tertiary-fixed-variant: '#92002a'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  headline-xl:
    fontFamily: manrope
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-xl-mobile:
    fontFamily: manrope
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: manrope
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: manrope
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: manrope
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 48px
  container-max: 1280px
---

## Marca y estilo

El sistema de diseño está pensado para transmitir empatía urgente y precisión clínica. Equilibra la calidez de la solidaridad humanitaria con la fiabilidad rigurosa de una plataforma de salud profesional. El público objetivo incluye profesionales médicos, voluntarios y donantes que necesitan claridad inmediata y una sensación de optimismo fundamentado.

La estética sigue un estilo **corporativo moderno** con acentos sutiles de **glasmorfismo**. Este enfoque garantiza alta legibilidad y una sensación sistemática, usando capas translúcidas y desenfoques suaves de fondo para evitar que la interfaz se sienta estéril o distante. El espacio en blanco se usa de forma generosa para reducir la carga cognitiva en interacciones de alto estrés, manteniendo el foco en la "vitalidad" y la "acción".

## Colores

La paleta se ancla en un teal primario, elegido por su asociación con salud, calma y estabilidad profesional. El navy secundario aporta solidez y se usa para jerarquía profunda y elementos estructurales. Un rosa terciario se reserva exclusivamente para acciones de alto impacto del "pulso" o alertas críticas, de modo que destaquen sin generar alarma innecesaria.

El modo predeterminado es claro, con una escala refinada de grises fríos que mantiene un entorno limpio y aireado y resalta los acentos primarios vibrantes.

## Tipografía

Este sistema de diseño utiliza una estrategia de doble tipografía para equilibrar carácter y utilidad. Los titulares usan **Manrope** por sus cualidades geométricas modernas, equilibradas y confiables. Su naturaleza ligeramente condensada permite títulos de alto impacto que siguen siendo cercanos.

Para el cuerpo de texto y los datos funcionales se emplea **Inter**. Su diseño sistemático y utilitario garantiza máxima legibilidad en distintas densidades de pantalla, especialmente en paneles de salud con muchos datos. En dispositivos móviles, las escalas de titulares se reducen de forma notable para mantener el contenido visible sin sacrificar la jerarquía tipográfica.

## Diseño y espaciado

El diseño se basa en una **rejilla fluida de 12 columnas** en escritorio, que pasa a una **rejilla de 4 columnas** en móvil. Todo el espaciado deriva de una línea base estricta de 8px para mantener armonía matemática y ritmo visual.

Los márgenes son generosos para reflejar el énfasis de la marca en claridad y "espacio para respirar". El contenido suele centrarse en un contenedor de ancho máximo en pantallas grandes para evitar líneas demasiado largas. Los componentes dentro de la rejilla deben usar relleno interno consistente (por ejemplo, 16px o 24px) alineado con el canal externo.

## Elevación y profundidad

La jerarquía visual se logra mediante **capas tonales** y **glasmorfismo**. En lugar de sombras pesadas tradicionales, la profundidad se comunica con cambios sutiles de color de superficie y desenfoques de fondo de baja opacidad (radio de desenfoque de 12px a 20px).

Cuando se requiere elevación para modales o botones flotantes, usa "sombras ambientales": sombras suaves y muy difusas (opacidad de 0.05 a 0.1) con un ligero tinte del navy secundario para que se integren en lugar de verse pesadas. Esto crea superficies apiladas ligeras, modernas y despejadas.

## Formas

El lenguaje de formas es consistentemente **redondeado**. Este radio de esquina (base de 0.5rem) equilibra suavidad y profesionalismo: más humano que los bordes rectos, pero más estructurado que elementos en forma de píldora. Los contenedores grandes, como tarjetas o modales, deben usar el token `rounded-xl` (1.5rem) para reforzar la naturaleza amable y protectora de la marca.

## Componentes

- **Botones:** Los botones primarios usan relleno sólido del teal primario con texto blanco. Los estados al pasar el cursor deben oscurecer ligeramente el relleno en lugar de aumentar la sombra. Usa `rounded-lg` en todos los botones para un objetivo de clic suave pero firme.
- **Tarjetas:** Usa fondo blanco con borde de 1px en un gris neutro muy claro. Para contenido destacado, aplica desenfoque de fondo sobre una superficie blanca semitransparente para reforzar la estética de glasmorfismo.
- **Campos de entrada:** Usa un tinte de fondo sutil en el rango neutral-50 con borde claro de 1px. Al enfocar, el borde debe pasar al teal primario con un resplandor exterior suave.
- **Chips:** Se usan para estado y filtrado. Deben tener forma de píldora (`rounded-full`) para distinguirse de botones más estructurados.
- **Indicadores de pulso:** Componente único de este sistema; un punto pequeño animado con brillo en rosa terciario, usado para indicar datos en vivo o alertas de salud urgentes.
- **Listas:** Las listas de alta densidad deben evitar bordes a favor de espacio en blanco limpio y rayado alternado sutil para mantener la sensación minimalista.