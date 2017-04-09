<?php
include_once 'util.php';

$questionN = count($config['question_values']);
$recoverN = 9;
$copingsN = 7;

ob_start();
?>
$options {
    default.properties "event;strict"
    default.order "auto"
}

// root concept of all concepts
_concept {
    strict 'true'

    event + '
        //// Set this to reset the score on each page reload (debugging purposes only)
        final boolean resetUserScore = false || (gale.req().getParameter("reset") != null);
        System.out.println(); // Start log with empty line for a concept
    '

    // count user visits of concepts
    #[visited]:Integer
    event + '
        boolean countclick = ${#pageCounter} > 0;
    '

    // this stores the asnwers each user has given to all the questions
    #[answers]:String

    // this stores the values of each coping technique of the user
    #[userScore]:String

    //
    #[pageCounter]:Integer
    event + '
        ${#pageCounter}++;
        //System.out.println("pageCounter: "+ ${#pageCounter});
    '

    // flag to indicate that new content is available
    #[new-content]:Boolean 'false'

    // Resources are .xml files with the same name as the concept. Unsuitable resources go
    // to unsuitable.xml first. "skip-unsuitable" is a request parameter that allows to
    // skip unsuitable.xml.
    #[new-session]:Boolean
    event +'
        String externalUrl = gale.req().getParameter("external");
        boolean external = (externalUrl != null && !"".equals(externalUrl.trim()));
        ${#new-session} = !"true".equals(gale.req().getParameter("open-session")) && !"".equals(${_concept->(personal)#last-session}) && !${_concept->(personal)#last-session}.equals(gale.req().getSession().getId());
        if (gale.req().getParameter("tags") != null)
            countclick = false;
        if ((external && ${#suitability}) || ${[[=_personal]]#first-time})
            countclick = false;
        if (${[[=_personal]]#review} && ${#new-session})
            countclick = false;
        if (${#unknown})
            countclick = false;
    '
    #resource ='~
	    if (${[[=_personal]]#first-time})
    		return "[[=intro.xhtml]]";
    	return "[[=layout.xhtml]]";
    '

    // knowledge definitions
    #[own-knowledge]:Double
    event + 'if (countclick) ${#own-knowledge} = 1; else if (${#own-knowledge} < 0.3) ${#own-knowledge} = 0.3;'
    #knowledge:Double ='avg(new Object[] {${<=(parent)#knowledge}, ${#own-knowledge}})'
    #known-all:Boolean ='${#knowledge} > 0.8'
    #known:Boolean ='${#own-knowledge} > 0.8'

    // prerequisites
    #available:Boolean ='${#known} || and(new Object[] {${=>(prereq)#known}, ${=>(prereq-all)#known-all}})'
    #suitability:Boolean ='and(new Object[] {${=>(prereq)#available}, ${=>(prereq-all)#known-all}}) && (!${_concept->(personal)#beginner} || ${#beginner})'

    // tags shown based on whether you view the concept for the first time
    #tags ='~
        if (${#visited} > 3) return "default";
        if ("true".equals(${?intro-first}))
            return (${#visited} > 1?"default":"intro");
        return "intro;default";
    '
    #tags.class '~
        if (!gale.isObject() && "long".equals(element.attributeValue("tag")))
            return "th-sidenote";
        return null;
    '

    // layout related stuff
    #layout.css ='~
        StringBuilder sb = new StringBuilder("[[=burnout.css]]");
        if (!${[[=_personal]]#first-time}) {
            if (!${[[=_personal]]#menu})
                sb.insert(0, "[[=nomenu.css]];");
            else
                sb.insert(0, "[[=menu.css]];");
            if (${[[=_personal]]#use-comments})
                sb.insert(0, "[[=comments.css]];");
            if (${[[=_personal]]#review})
                sb.insert(0, "[[=review.css]];");
        }
        sb.append(";http://fonts.googleapis.com/css?family=Carme");
        return sb.toString();
    '
    #layout.title '"Burnout - "+${.}.getTitle()'
    #link.hide '!${#hierarchy}'
    #link.classexpr '~
        return "neutral";
    '


    #link.iconlist 'null'
    #unknown:Boolean ="false"
    #[beginner]:Boolean ='!"false".equals(${?beginner})'
    #hierarchy:Boolean ='(${_concept->(personal)#menu} || (${#suitability} && !${#unknown})) && ${->(parent)#suitability}'

    // used to mark a concept that was too advanced but understood anyway
    #[mark]:Integer {
        event '
            Concept[] list = ${->(prereq)};
            for (int i=0;i < list.length;i++) {
                URI uri = session.resolve(list[i].getUri()+"#own-knowledge");
                EntityValue ev = (EntityValue)session.get(uri);
                session.put(uri, new EntityValue(uri, 1d));
            }
            list = ${->(prereq-all)};
            for (int i=0;i < list.length;i++) {
                URI uri = session.resolve(list[i].getUri()+"#mark-all");
                EntityValue ev = (EntityValue)session.get(uri);
                session.put(uri, new EntityValue(uri, ((Integer)ev.getValue())+1));
            }
        '
    }
    #[mark-all]:Integer {
        event '
            ${#own-knowledge} = 1d;
            Concept[] list = ${<-(parent)};
            for (int i=0;i < list.length;i++) {
                URI uri = session.resolve(list[i].getUri()+"#mark-all");
                EntityValue ev = (EntityValue)session.get(uri);
                session.put(uri, new EntityValue(uri, ((Integer)ev.getValue())+1));
            }
        '
    }

    event +'
        if (countclick) {${#visited}++; ${#new-content} = false;}
    '
    ->(personal)_personal

    // enable a reviewer to return to the previous concept when he begins a new session
    event +'
        if (${_concept->(personal)#review} && (countclick || "true".equals(gale.req().getParameter("open-session")))) {
            ${_concept->(personal)#last-concept} = ${.}.getUriString();
            ${_concept->(personal)#last-session} = gale.req().getSession().getId();
        }
    '

}

// root concept defining variables that change depending on how often they are viewed
_variable {
    // count the number of views
    #[count]:Integer
    event +"${#count}++;"
    // define the resource as based on the number of views and the #value string
    #resource:String "content:#content"
    #content:String ='pick(${#count}, ${#value})'
    // a semicolon seperated list of strings that represents the values (overwritten in
    // children)
    #value:String ='"variable (" + ${.}.getUri() + ")"'
}

// personal settings for the thesis
_personal {
    // menu on the left or as drop down
    #[menu]:Boolean 'true'
    // first time to visit the thesis
    #[first-time]:Boolean 'true'
    // role
    #[role] 'intermediate' {
        event '
            if ("beginner".equals(changed.newValue)) {
                ${#beginner} = true;
            }
            if ("intermediate".equals(changed.newValue)) {
                ${#beginner} = false;
            }
            if ("expert".equals(changed.newValue)) {
                ${application#mark-all}++;
                ${#analytic-count} = 512;
                ${#beginner} = false;
            }
        '
    }
    #[review]:Boolean 'false'
    #[beginner]:Boolean 'false'
    #[beginner-level]:Integer '0'
    #[use-comments]:Boolean 'false'
    #[last-concept]
    #[last-session]
    #[analytic-count]:Integer '-2' {
        event 'if ((Integer)changed.newValue == 0 && changed.diff != 0) ${#analytic-count} = changed.diff*2;'
    }
    #analytic:Boolean ='${#analytic-count} > 0'
}

application {->(extends)_concept
    title 'Welcome to Incendio'
    no-title 'false'
}

introduction {->(extends)_concept
    ->(parent)application
    #available:Boolean ='${#known}'
    title 'Introduction'
}

// The questions concept to show a single question
question {->(extends)_concept
    ->(parent)application
    #available:Boolean ='${#known}'
    title 'Question'
    no-title 'true'

    // Retrieve the previous question index from the GET pq parameter
    // and store it in `previousQuestion`
    #[previousQuestion]:Integer
    event + '
        int previousQuestion;
        try{
            previousQuestion = Integer.parseInt(gale.req().getParameter("pq"));
        }catch(Exception e){
            System.out.println("GET `pq` not set: " + String.valueOf(e));
            previousQuestion = -1;
        }
        ${#previousQuestion} = previousQuestion;
    '

    // Retrieve the previous question index from the GET pa parameter
    // and store it in `previousAnswer`
    #[previousAnswer]:Integer
    event + '
        int previousAnswer;
        try{
            previousAnswer = Integer.parseInt(gale.req().getParameter("pa"));
        } catch(Exception e) {
            System.out.println("GET `pa` not set: " + String.valueOf(e));
            previousAnswer = -1;
        }
        ${#previousAnswer} = previousAnswer;
    '

    // Get the categories of all question from the config file
    event + '
        <?php
        PHP_PRINT( array_map( function ( $el ) {
            return $el[0];
        }, $config['questions'] ), 'categories', 'int' );
        ?>
    '

    event + '
        // Retrieve the question answers form the user model
        String answersString = ${#answers};
        int[] answers;
        if(resetUserScore || "".equals(answersString)){
            <?php $defaultAnswers = array_fill(0, $questionN, -1);
            PHP_PRINT($defaultAnswers, 'defaultAnswers', 'int'); // 2d ?>
            answers = defaultAnswers;
        } else {
            <?php JAVA_DECODE('answersString', 'answers', 1, true, 'int'); ?>
        }
        // int[] answers is retrieved

        // Store the new questions answer and retrieve all not answered questions
        int[] questionOrdering = new int[<?=$questionN?>]; int ix = 0;
        boolean newAnswer = true;
        Arrays.fill(questionOrdering, -1);
        int catCount = 0;
        int prevCat = -1;
        for (int i = 0; i < answers.length; i++) {
            if(i == previousQuestion) {
                newAnswer = (answers[i] == -1); // It was -1 before
                answers[i] = previousAnswer;
                System.out.println("Stored answer " + previousAnswer + " for question " + previousQuestion);
            }
            if(answers[i] == -1) {
                questionOrdering[ix++] = i;
            }
            int currentCat = categories[i]/100;
            if(prevCat == -1 || prevCat == currentCat) catCount++;
            else catCount=0;
            prevCat = currentCat;
        }
        questionOrdering = Arrays.copyOf(questionOrdering, ix);
        // int[] questionOrdering contains all not answered questions

        // Store the updated answers array
        <?php JAVA_ENCODE('answers', 'answersString', 1, true) ?>
        ${#answers} = answersString;
    '

    event + '
        // Get the question coping strategy values of all question from the config file
        <?php PHP_PRINT($config['question_values'], 'questionScores'); // 2d ?>

        // Read coping strategy values of the user
        float[] userScore;
        String userScoreStr = ${#userScore};
        if(resetUserScore || "".equals(userScoreStr)){
            <?php
            PHP_PRINT($config['default_user_profile'], 'defaultUserScore');
            JAVA_ENCODE('defaultUserScore', 'defaultUserScoreStr', 1); ?>
            userScore = defaultUserScore;
            System.out.println("Users score is reset to defaultUserScoreStr = " + defaultUserScoreStr);
        } else {
            <?php JAVA_DECODE('userScoreStr', 'userScore', 1, true); ?>
        }

        if(previousQuestion != -1 && previousAnswer != -1 && newAnswer){
            // Apply the previously answered question to the users score
            float answerEffect = previousAnswer * .5f;
            for(int i = 0; i < <?=($copingsN)?>; i++){
                userScore[i] += answerEffect * questionScores[previousQuestion][i];
            }
        }

        // Store the users score to gale
        <?php JAVA_ENCODE('userScore', 'userScoreStr', 1, true); ?>
        System.out.println("New user score is userScoreStr = " + userScoreStr);
        ${#userScore} = userScoreStr;

        // Get the questions content from the config file
        <?php PHP_PRINT($config['questions'], 'questions', 'String'); ?>

        // Get the next coping strategy that should be asked
        <?php PHP_PRINT(range(0, $copingsN-1), 'copings', 'int'); ?>
        int[] minCopingIndices = new int[copings.length]; int mcii = 0;
        // First get the (smallest) coping index that is lowest;
        for(int i = 1; i < userScore.length; i++){
            if (Math.abs(userScore[i]) < Math.abs(userScore[minCopingIndices[mcii]])) {
                // New minimum found
                mcii = 0;
                minCopingIndices[mcii] = i;
            } else if (Math.abs(userScore[i]) == Math.abs(userScore[minCopingIndices[mcii]])) {
                minCopingIndices[++mcii] = i;
            }
        }
        minCopingIndices = Arrays.copyOf(minCopingIndices, mcii);

        // Get the most impactfull questions for each coping strategy from the config file
        <?php PHP_PRINT($config['coping_category'], 'copingQuestions', 'int'); ?>

        // Find all plausible next questions
        int[] nextQuestion = new int[questionOrdering.length]; int nextQuestionI = 0;
        for(int q = 0; q < questionOrdering.length; q++){
            int question = questionOrdering[q];
            for (int i = 0; i < minCopingIndices.length; i++) {
                int[] cop2cat = copingQuestions[minCopingIndices[i]];
                if (Arrays.binarySearch(cop2cat, question) >= 0) {
                    nextQuestion[nextQuestionI++] = question;
                    break;
                }
            }
        }
        nextQuestion = Arrays.copyOf(nextQuestion, nextQuestionI);

        int nextQuestionIndex;
        if(nextQuestion.length > 0){
            // A more plausible next question is found
            System.out.println("picked plausible next question form " + nextQuestion.length);
            nextQuestionIndex = nextQuestion[(int)(Math.random() * nextQuestion.length)];
        } else if (questionOrdering.length > 0){
            // There is still a question unanswered
            System.out.println("picked random next question form " + questionOrdering.length
                + " where minCopingIndices.length = " + minCopingIndices.length);
            nextQuestionIndex = questionOrdering[(int)(Math.random() * questionOrdering.length)];
        } else {
            // Otherwise just pick the first
            System.out.println("First question picked");
            nextQuestionIndex = 0;
        }
    '

    // calculate the progressbar percentage
    #[percentage]:Integer
    event + '
        int percentage = (int)((double)(<?=$questionN?> - questionOrdering.length)/<?=$questionN?>*100);
        System.out.println("percentage = " + percentage);
        ${#percentage} = percentage;
    '

    // Show a joke every 4 questions
    #[joke]:String
    event + '
        String joke = "";
        if(questionOrdering.length > 0 && questionOrdering.length % 4 == 0){
            <?php PHP_PRINT($config['jokes'], 'jokes', 'String'); ?>
            joke = jokes[(questionOrdering.length / 4) % jokes.length];
        }
        ${#joke} = joke;
    '

    // Store if the previous qeustion is answered as an image
    #[answeredImage]:Integer
    event + '
        if ("i".equals(gale.req().getParameter("a"))) {
            int answeredImage = ${#answeredImage};
            answeredImage++;
            ${#answeredImage} = answeredImage;
        }
    '

    // Store if the previous qeustion is answered as text
    #[answeredText]:Integer
    event + '
        if ("t".equals(gale.req().getParameter("a"))) {
            ${#answeredText}++;
        }
    '

    // Set the question id
    #[questionIndex]:Integer
    event + '
        ${#questionIndex} = nextQuestionIndex;
    '

    // Retieve the text based question
    #[questionTxt]:String
    event + '
        ${#questionTxt} = questions[nextQuestionIndex][1];
    '
    // Retrieve the image url for the image based question
    #[questionImg]:String
    event + '
        ${#questionImg} = questions[nextQuestionIndex][2];
    '
    // (depr) Retrieve the hard text for an question
    #[questionTxtHard]:String
    event + '
        ${#questionTxtHard} = questions[nextQuestionIndex][3];
    '

    // Retrieve the answers
    #[answer1Text]:String
    event + '
        ${#answer1Text} = questions[nextQuestionIndex][4];
    '
    #[answer2Text]:String
    event + '
        ${#answer2Text} = questions[nextQuestionIndex][5];
    '
    #[answer3Text]:String
    event + '
        ${#answer3Text} = questions[nextQuestionIndex][6];
    '
}
settings {->(extends)_concept
    ->(parent)application
    title 'Settings'
}
results {->(extends)_concept
    ->(parent)application
    title 'Results'

    // Calculate in the final recommendation
    #[recString]:String
    event + '
        // Read userScore
        float[] userScore;
        String userScoreStr = ${#userScore};
        if(resetUserScore || "".equals(userScoreStr)){
            <?php PHP_PRINT($config['default_user_profile'], 'userScore', 'float', true);
            // Also write the userScore back to the variable if its not set
            JAVA_ENCODE('userScore', 'userScoreStr', 1, true); echo '${#userScore} = userScoreStr;'; ?>
            System.out.println("Results: reset userScoreStr = " + userScoreStr);
        } else {
            <?php JAVA_DECODE('userScoreStr', 'userScore', 1, true); ?>
            System.out.println("Results: read userScoreStr = " + userScoreStr);
        }
        <?php PHP_PRINT($config['stressor_to_recovery'], 'stress2Rec', 'int'); ?>

        float[] rec = new float[<?=$recoverN?>];
        float totalBurnout = userScore[0];
        for(int s = 1; s < userScore.length; s++){
            float stressorScore = userScore[s];
            int stressorIndex = s - 1;
            for(int c = 0; c < stress2Rec[stressorIndex].length; c++) {
                rec[stress2Rec[stressorIndex][c]] += stressorScore;
            }
        }

        // rec now contains the recovery values
        <?php JAVA_ENCODE('rec', 'recString', 1); ?>
        ${#recString} = recString;
    '

    // Pass the rec array to JS
    #[recoveryChoices]:String
    event + '
        recString = ${#recString};
        <?php JAVA_DECODE('recString', 'rec', 1, true); /*rec is defined somewhere else*/ ?>
        <?php JAVA_ENCODE('rec', 'recJsArrayStr', 1, false, true); ?>
        ${#recoveryChoices} = recJsArrayStr;
    '

    // Pass the recovery text form the config file to JS
    #[recoveryJsArray]:String
    event + '
        <?php JS_PRINT($config['recovery'], 'recoveryJsArrayStr'); ?>
        System.out.println("recoveryJsArrayStr: "+recoveryJsArrayStr);
        ${#recoveryJsArray} = recoveryJsArrayStr;
    '

    // Calculate the final burnpout score
    #[burnoutScore]:String
    event + '
        final float maxBurnout = 3.25f;
        if(totalBurnout < 0){
            totalBurnout = 0;
        } else {
            totalBurnout /= maxBurnout;
        }
        ${#burnoutScore} = String.valueOf(totalBurnout);
    '
}
<?php
file_put_contents('concepts.gam', ob_get_clean());
