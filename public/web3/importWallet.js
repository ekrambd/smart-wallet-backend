const { ethers } = require("ethers");

let input = "";

process.stdin.on("data", (chunk) => {
    input += chunk.toString();
});

process.stdin.on("end", () => {

    try {

        const payload = JSON.parse(input);

        if (!payload.type) {
            throw new Error("Type required");
        }

        let wallet;

        /*
        |--------------------------------------------------------------------------
        | MNEMONIC FLOW
        |--------------------------------------------------------------------------
        */

        if (payload.type === "mnemonic") {

            const mnemonicArray = payload.data;

            if (!Array.isArray(mnemonicArray)) {
                throw new Error("Invalid mnemonic format");
            }

            const phrase = mnemonicArray
                .sort((a, b) => a.serial - b.serial)
                .map(i => i.word)
                .join(" ");

            // ethers v6 safe wallet create
            wallet = ethers.Wallet.fromPhrase
                ? ethers.Wallet.fromPhrase(phrase)
                : ethers.Wallet.fromMnemonic(phrase);
        }

        /*
        |--------------------------------------------------------------------------
        | PRIVATE KEY FLOW
        |--------------------------------------------------------------------------
        */

        else if (payload.type === "private_key") {

            wallet = new ethers.Wallet(payload.data);
        }

        else {
            throw new Error("Invalid type");
        }

        /*
        |--------------------------------------------------------------------------
        | RESULT
        |--------------------------------------------------------------------------
        */

        const result = {
            address: wallet.address,
            privateKey: wallet.privateKey
        };

        console.log(JSON.stringify(result));

    } catch (err) {

        console.log(JSON.stringify({
            error: true,
            message: err.message
        }));
    }
});